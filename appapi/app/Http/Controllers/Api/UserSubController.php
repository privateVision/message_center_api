<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Redis;
use App\Model\Procedures;
use App\Model\UcuserSub;
use App\Model\ZyGame;
use App\Model\UcuserSubTotal;

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
            throw new ApiException(ApiException::Remind, trans('messages.nickname_not_exists'));
        }

        $user_sub = UcuserSub::tableSlice($this->user->ucid)->find($id);
        if(!$user_sub || $user_sub->ucid != $this->user->ucid) {
            throw new ApiException(ApiException::Remind, trans('messages.modify_usersub_not_exists'));
        }

        $user_sub->name = $nickname;
        $user_sub->save();

        return ['result' => true];
    }

    public function ListAction() {
        $pid = $this->procedure->pid;

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

        return [
            'allow_num' => intval($this->procedure_extend->allow_num),
            'data' => $data
        ];
    }

    public function NewAction() {
        // 自旋锁
        return Redis::spin_lock(sprintf('user_sub_new_lock_%s', $this->user->ucid), function() {

            $pid = $this->procedure->pid;
            $rid = $this->parameter->tough('_rid');

            $allow_num = $this->procedure_extend->allow_num;
/*
            $redisfield = $this->user->ucid .'_'. $pid;
            $user_sub_num = Redis::hget('user_sub_num', $redisfield);
            if(!$user_sub_num) {
                $reset = true;
                $user_sub_num = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->count();
            }
*/
            // XXX 理论上不存在user_sub_total找不到记录的情况
            // 因为玩家如果已在游戏中，此时上线该代码，可能会造成问题，所以加了这个判断
            $user_sub_total_id = joinkey($pid, $this->user->ucid);
            $user_sub_total = UcuserSubTotal::find($user_sub_total_id);

            if($user_sub_total) {
                if ($allow_num <= $user_sub_total->total) {
                    throw new ApiException(ApiException::Remind, trans('messages.usersub_much'));
                }

                $user_sub_num = $user_sub_total->total + 1;
            } else {
                $user_sub_num = 1;
            }

            $cp_uid = $this->user->ucid . sprintf('%05d%02d', $pid, $user_sub_num);
            
            $user_sub = UcuserSub::tableSlice($this->user->ucid);
            $user_sub->id = $cp_uid;
            $user_sub->ucid = $this->user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $cp_uid;
            $user_sub->name = "小号" . $user_sub_num;
            $user_sub->priority = 0;
            $user_sub->last_login_at = null;
            $user_sub->save();

            if(!$user_sub_total) {
                $user_sub_total = new UcuserSubTotal();
                $user_sub_total->id = $user_sub_total_id;
                $user_sub_total->pid = $pid;
                $user_sub_total->ucid = $this->user->ucid;
                $user_sub_total->total = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->count();
                $user_sub_total->save();
            } else {
                $user_sub_total->increment('total', 1);
            }
/*
            if(isset($reset)) {
                Redis::hset('user_sub_num', $redisfield, $user_sub_num);
            } else {
                Redis::hincrby('user_sub_num', $redisfield, 1);
            }
*/
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