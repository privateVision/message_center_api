<?php

namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\Procedures;
use App\Model\TotalFeePerUser;
use App\Model\UcuserSub;
use App\Model\UcuserRole;
use App\Model\UcuserSubService;

class AuthAccountController extends Controller
{
    //好充账号验证,并且返回小号列表，角色列表
    public function AuthAccountAction()
    {
        $username = $this->parameter->tough('username');
        $password = $this->parameter->tough('password');
        $gameCenterId = $this->parameter->tough('gameCenterId');

        $user = Ucuser::where('mobile', $username)->orWhere('uid', $username)->orWhere('email', $username)->first();

        if(!$user||!$user->checkPassword($password)){
            throw new ApiException(ApiException::Remind, trans('messages.login_fail'));
        }

        if($user->getIsFreezeAttribute()){
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze'));
        }

        $pids = Procedures::where('gameCenterId', $gameCenterId)->get(['pid'])->toArray();

        if(!$pids){
            throw new ApiException(ApiException::Remind, trans('messages.game_not_found'));
        }

        $total_fee = TotalFeePerUser::where('ucid', $user->ucid)->whereIn('pid', $pids)->sum('total_fee');

        $userSubs = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->whereIn('pid', $pids)->where('is_freeze', 0)->get(['id', 'name', 'pid'])->toArray();

        foreach ($userSubs as $k=>$v){
            $userSubs[$k]['user_role'] = UcuserRole::tableSlice($v['pid'])->where('user_sub_id', $v['id'])->get();
        }

        return [
            'username'=>$user->uid,
            'uid'=>$user->ucid,
            'mobile'=>$user->mobile,
            'subInfo'=>$userSubs,
            'total_fee'=>$total_fee,
        ];
    }

    public function FreezeSubAction()
    {
        $subUserId = $this->parameter->tough('sub_user_id');
        $srcUcid = $this->parameter->tough('ucid');
        $status = $this->parameter->tough('status');
        $serviceUid = $this->parameter->get('service_uid', 0);
        $serviceid = $this->parameter->get('service_id', 0);
        $otherUcid = $this->parameter->get('other_ucid', 0);

        $userSub = UcuserSub::tableSlice($srcUcid)->where('id', $subUserId)->where('ucid', $srcUcid)->first();

        if(!$userSub){
            throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));
        }

        switch ($status){
            //小号状态，0待审核，1审核中，2审核通过，3审核不通过，4交易成功, 5待审核状态时取消发布， 6审核通过后下架
            case 0:
                $serviceUcid = 0;

                //只有状态正常才能卖
                if($userSub->is_freeze!=0)throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));

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
                $serviceid = $ucuserSubService->id;
                $ucuserSubService->getConnection()->commit();

                if(!$serviceid)throw new ApiException(ApiException::Error);

                return [
                    'service_id'=>$serviceid
                ];
                break;
            case 1:

                $serviceUcid = Ucuser::where('uid', $serviceUid)->first(['ucid']);
                if(!$serviceUcid) throw new ApiException(ApiException::Remind, trans('messages.service_user_err'));

                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 0)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->ucid = $serviceUcid->ucid;
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
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 2)->first();
                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();

                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $userSub = UcuserSub::tableSlice($ucuserSubService->src_ucid)->where('id', $ucuserSubService->user_sub_id)->where('is_freeze', 1)->first();

                if(!$userSub)throw new ApiException(ApiException::Remind, trans('messages.sub_user_err'));

                if(!Ucuser::where('ucid', $otherUcid)->first())throw new ApiException(ApiException::Remind, trans('messages.buy_user_err'));

                $otherUserSub = UcuserSub::tableSlice($otherUcid);
                $otherUserSub->id = $userSub->id;
                $otherUserSub->ucid = $otherUcid;
                $otherUserSub->pid = $userSub->pid;
                $otherUserSub->rid = $userSub->rid;
                $otherUserSub->old_rid = $userSub->old_rid;
                $otherUserSub->cp_uid = $userSub->cp_uid;
                $otherUserSub->name = $userSub->name;
                $otherUserSub->priority = $userSub->priority;
                $otherUserSub->last_login_at = $userSub->last_login_at;
                $otherUserSub->is_freeze = 0;/*print_r($otherUserSub);die;*/

                $userSub->delete();

                //角色
                $ucuserRoles = UcuserRole::tableSlice($userSub->pid)->where('user_sub_id', $userSub->id)->get();
                foreach ($ucuserRoles as &$ucuserRole){
                    $ucuserRole->ucid = $otherUcid;
                    $ucuserRole->id = joinkey($userSub->pid, $otherUcid, $userSub->id, $ucuserRole->zoneId, $ucuserRole->roleId);

                    $ucuserRole->save();
                }



                $otherUserSub->save();



                $ucuserSubService->getConnection()->commit();
                break;

            case 5:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 0)->first();
                if(!$ucuserSubService)throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                if($userSub->is_freeze===0)throw new ApiException(ApiException::Remind, trans('messages.sub_user_normal'));

                $userSub->is_freeze = 0;
                $userSub->save();

                $ucuserSubService->getConnection()->commit();
                break;

            case 6:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 2)->first();
                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                if($userSub->is_freeze==0)throw new ApiException(ApiException::Remind, trans('messages.sub_user_normal'));

                $userSub->is_freeze = 0;
                $userSub->save();
                $ucuserSubService->getConnection()->commit();
                break;
        }

        return [];
    }
}
