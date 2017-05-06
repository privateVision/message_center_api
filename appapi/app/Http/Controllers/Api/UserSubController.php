<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Redis;
use App\Model\ProceduresExtend;
use App\Model\Procedures;
use App\Model\UcuserSub;
use App\Model\ZyGame;

class UserSubController extends AuthController
{
    public function GameListAction() {
        $result = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->orderBy('priority', 'desc')->get();

        $data = [];
        foreach($result as $v) {
            $pid = $v->pid;

            if($pid < 100) continue;

            if(!isset($data[$pid])) {
                $procedure = Procedures::from_cache($pid);
                if(!$procedure) continue;
                $game = ZyGame::from_cache($procedure->gameCenterId);
                if(!$game) continue;

                $data[$pid] = [
                    'icon' => $game->cover,
                    'name' => $game->name,
                    'roles' => [],
                ];
            }

            $data[$pid]['roles'][] = [
                'id' => $v->id,
                'nickname' => $v->name,
                'is_freeze' => $v->is_freeze,
            ];
        }

        return array_values($data);
    }

    public function SetNicknameAction() {
        $id = $this->parameter->tough('id');
        $nickname = $this->parameter->tough('nickname', 'sub_nickname');

        $count = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('name', $nickname)->count();
        if($count) {
            throw new ApiException(ApiException::Remind, "修改失败，昵称已经存在");
        }

        $user_sub = UcuserSub::tableSlice($this->user->ucid)->find($id);
        if(!$user_sub || $user_sub->ucid != $this->user->ucid) {
            throw new ApiException(ApiException::Remind, "修改失败，小号不存在");
        }

        $user_sub->name = $nickname;
        $user_sub->save();

        return ['result' => true];
    }

    public function ListAction() {
        $pid = $this->parameter->tough('_appid');

        $data = [];

        $user_sub = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->orderBy('name', 'asc')->get();
        foreach($user_sub as $v) {
            if($v->pid < 100) continue;
            $data[] = [
                'id' => $v->id,
                'openid' => $v->cp_uid,
                'name' => $v->name,
                'is_default' => $v->id === $this->session->user_sub_id,
                'status' => $v->is_freeze ? 1 : (!$v->last_login_at ? 2 : 0),
                'last_login_at' => strval($v->last_login_at),
            ];
        }

        $config = ProceduresExtend::from_cache($pid);

        return [
            'allow_num' => $config && $config['allow_num'] ? (int)$config['allow_num'] : 1,
            'data' => $data
        ];
    }

    public function NewAction() {
        // 自旋锁
        return Redis::spin_lock(sprintf('user_sub_new_lock_%s', $this->user->ucid), function() {
            $pid = $this->parameter->tough('_appid');
            $rid = $this->parameter->tough('_rid');

            $config = ProceduresExtend::from_cache($pid);
            $allow_num = $config && $config['allow_num'] ? (int)$config['allow_num'] : 1;

            $redisfield = $this->user->ucid .'_'. $pid;
            $user_sub_num = Redis::hget('user_sub_num', $redisfield);
            if(!$user_sub_num) {
                $reset = true;
                $user_sub_num = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->count();
            }

            if($allow_num <= $user_sub_num) {
                throw new ApiException(ApiException::Remind, "小号创建数量已达上限");
            }

            $user_sub_num++;

            $user_sub = UcuserSub::tableSlice($this->user->ucid);
            $user_sub->id = uuid($this->user->ucid);
            $user_sub->ucid = $this->user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $this->user->ucid . sprintf('%05d%02d', $pid, $user_sub_num);
            $user_sub->name = "小号" . ($user_sub_num);
            $user_sub->priority = 0;
            $user_sub->last_login_at = null;
            $user_sub->save();

            if(isset($reset)) {
                Redis::hset('user_sub_num', $redisfield, $user_sub_num);
            } else {
                Redis::hincrby('user_sub_num', $redisfield, 1);
            }

            return [
                'id' => $user_sub->id,
                'openid' => $user_sub->cp_uid,
                'name' => $user_sub->name,
                'status' => 2,
                'is_default' => false,
                'last_login_at' => "",
            ];
        });
    }
}