<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Redis;
use App\Model\ProceduresExtend;
use App\Model\Procedures;
use App\Model\Ucuser;
use App\Model\UcuserSub;
use App\Model\ZyGame;
use App\Model\UcuserSubService;

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

        return [
            'allow_num' => intval($this->procedure_extend->allow_num),
            'data' => $data
        ];
    }

    public function NewAction() {
        // 自旋锁
        return Redis::spin_lock(sprintf('user_sub_new_lock_%s', $this->user->ucid), function() {
            $pid = $this->parameter->tough('_appid');
            $rid = $this->parameter->tough('_rid');

            $allow_num = $this->procedure_extend->allow_num;

            $redisfield = $this->user->ucid .'_'. $pid;
            $user_sub_num = Redis::hget('user_sub_num', $redisfield);
            if(!$user_sub_num) {
                $reset = true;
                $user_sub_num = UcuserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->count();
            }

            if($allow_num <= $user_sub_num) {
                throw new ApiException(ApiException::Remind, trans('messages.usersub_much'));
            }

            $user_sub_num++;

            $cp_uid = $this->user->ucid . sprintf('%05d%02d', $pid, $user_sub_num);
            
            $user_sub = UcuserSub::tableSlice($this->user->ucid);
            $user_sub->id = $cp_uid;
            $user_sub->ucid = $this->user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $cp_uid;
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

    public function FreezeSubAction()
    {
        $subUserId = $this->parameter->tough('sub_user_id');
        $srcUcid = $this->parameter->tough('ucid');
        $status = $this->parameter->tough('status');
        $serviceUid = $this->parameter->get('service_uid', 0);
        $serviceid = $this->parameter->get('service_id', 0);

        $userSub = UcuserSub::tableSlice($srcUcid)->where('id', $subUserId)->where('ucid', $srcUcid)->first();

        if(!$userSub){
            throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));
        }

        switch ($status){
            //小号状态，0待审核，1审核中，2审核通过，3审核不通过，4交易成功
            case 0:
                $serviceUcid = 0;

                $ucuserSubService = new UcuserSubService();
                $ucuserSubService->getConnection()->beginTransaction();
                $ucuserSubService->ucid = $serviceUcid;
                $ucuserSubService->user_sub_id = $subUserId;
                $ucuserSubService->pid = $userSub->pid;
                $ucuserSubService->src_ucid = $srcUcid;
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $userSub->is_freeze = 1;
                $userSub->save();
                $serviceid = $userSub->id;
                $ucuserSubService->getConnection()->commit();

                if(!$serviceid)throw new ApiException(ApiException::Error);
                break;
            case 1:

                $serviceUcid = Ucuser::where('uid', $serviceUid)->first(['ucid']);
                if(!$serviceUcid) throw new ApiException(ApiException::Remind, trans('messages.service_user_err'));

                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 0)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->ucid = $serviceUid;
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                break;
            case 2:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 1)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));
                $ucuserSubService->status = $status;
                $ucuserSubService->save();
                break;
            case 3:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 1)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();

                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $userSub = UcuserSub::tableSlice($ucuserSubService->src_ucid)->where('id', $ucuserSubService->user_sub_id)->where('is_freeze', 1)->first();

                if(!$userSub)throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));

                $userSub->is_freeze = 0;
                $userSub->save();

                $ucuserSubService->getConnection()->commit();

                break;
            case 4:
                $otherUcid = $this->parameter->tough('other_ucid');

                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 3)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();

                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $userSub = UcuserSub::tableSlice($ucuserSubService->src_ucid)->where('id', $ucuserSubService->user_sub_id)->where('is_freeze', 1)->first();

                if(!$userSub)throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));

                if(!Ucuser::where('ucid', $otherUcid)->first())throw new ApiException(ApiException::Remind, trans('messages.buy_user_err'));

                $otherUserSub = UcuserSub::tableSlice($otherUcid);
                $otherUserSub->id = $userSub->id;
                $otherUserSub->ucid = $userSub->ucid;
                $otherUserSub->pid = $userSub->pid;
                $otherUserSub->rid = $userSub->rid;
                $otherUserSub->old_rid = $userSub->old_rid;
                $otherUserSub->cp_uid = $userSub->cp_uid;
                $otherUserSub->name = $userSub->name;
                $otherUserSub->priority = $userSub->priority;
                $otherUserSub->last_login_at = $userSub->last_login_at;
                $otherUserSub->save();

                $userSub->delete();

                $ucuserSubService->getConnection()->commit();
                break;
        }

        return [
            'service_id'=>$subUserId
        ];
    }
}