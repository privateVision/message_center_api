<?php

namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\FLog;
use App\Model\UcuserSubServiceLog;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\Procedures;
use App\Model\TotalFeePerUser;
use App\Model\UcuserSub;
use App\Model\UcuserRole;
use App\Model\UcuserSubService;
use App\Model\UcuserSubTotal;

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

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $serviceUcid;
                $ucuserSubServiceLog->user_sub_id = $subUserId;
                $ucuserSubServiceLog->pid = $userSub->pid;
                $ucuserSubServiceLog->src_ucid = $srcUcid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

                $ucuserSubService->getConnection()->commit();

                if(!$serviceid)throw new ApiException(ApiException::Error);

                return [
                    'service_id'=>$serviceid
                ];
                break;
            case 1:

                $serviceUcid = Ucuser::where('uid', $serviceUid)->first(['ucid']);
                if(!$serviceUcid) throw new ApiException(ApiException::Remind, trans('messages.service_user_err'));

                //一个客服同时只能审核一个账号
                if(UcuserSubService::where('ucid', $serviceUcid->ucid)->where('status', 1)->first()){
                    throw new ApiException(ApiException::Remind, trans('messages.service_limit_1'));
                }

                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 0)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->ucid = $serviceUcid->ucid;
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $serviceUcid->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

                break;
            case 2:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 1)->first();

                if(!$ucuserSubService) throw new ApiException(ApiException::Remind, trans('messages.service_err'));
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $ucuserSubService->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();
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

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $ucuserSubService->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

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

                $user_sub_total_id = joinkey($userSub->pid, $otherUcid);
                $user_sub_total = UcuserSubTotal::find($user_sub_total_id);
                $user_sub_total->increment('total', 1);

                $otherUserSub = UcuserSub::tableSlice($otherUcid);
                $otherUserSub->id = $userSub->id;
                $otherUserSub->ucid = $otherUcid;
                $otherUserSub->pid = $userSub->pid;
                $otherUserSub->rid = $userSub->rid;
                $otherUserSub->old_rid = $userSub->old_rid;
                $otherUserSub->cp_uid = $userSub->cp_uid;
                $otherUserSub->name = '小号'.$user_sub_total->total;
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

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $ucuserSubService->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

                $ucuserSubService->getConnection()->commit();
                break;

            case 5:
                $ucuserSubService = UcuserSubService::where('id', $serviceid)->where('status', 0)->first();
                if(!$ucuserSubService)throw new ApiException(ApiException::Remind, trans('messages.service_err'));

                $ucuserSubService->getConnection()->beginTransaction();
                $ucuserSubService->status = $status;
                $ucuserSubService->save();

                if($userSub->is_freeze==0)throw new ApiException(ApiException::Remind, trans('messages.sub_user_normal'));

                $userSub->is_freeze = 0;
                $userSub->save();

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $ucuserSubService->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

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

                $ucuserSubServiceLog = new UcuserSubServiceLog();
                $ucuserSubServiceLog->ucid = $ucuserSubService->ucid;
                $ucuserSubServiceLog->user_sub_id = $ucuserSubService->user_sub_id;
                $ucuserSubServiceLog->pid = $ucuserSubService->pid;
                $ucuserSubServiceLog->src_ucid = $ucuserSubService->src_ucid;
                $ucuserSubServiceLog->status = $status;
                $ucuserSubServiceLog->save();

                $ucuserSubService->getConnection()->commit();
                break;
        }

        return [];
    }

    //f币变动
    public function ChangeFAction()
    {
        $username = $this->parameter->tough('username');
        $amount = (float)$this->parameter->tough('amount');
        $type = $this->parameter->tough('type');
        $msg = $this->parameter->tough('msg');
        $pid = $this->parameter->tough('_appid');

        $ucuser = Ucuser::where('uid', $username)->first();

        if(!$ucuser)throw new ApiException(ApiException::Remind, trans('messages.user_not_exists'));

        if($ucuser->is_freeze==0)throw new ApiException(ApiException::Remind, trans('messages.freeze'));

        if($ucuser->balance + $amount<0)throw new ApiException(ApiException::Remind, trans('messages.balance_not_enough'));

        $ucuser->getConnection()->beginTransaction();
        $ucuser->balance = $ucuser->balance + $amount;
        $ucuser->save();

        $fLog = new FLog();
        $fLog->pid = $pid;
        $fLog->amount = $amount;
        $fLog->ucid = $ucuser->ucid;
        $fLog->type = $type;
        $fLog->msg = $msg;
        $fLog->create_time = time();
        $fLog->update_time = time();
        $fLog->save();

        $ucuser->getConnection()->commit();

        return [];
    }
}
