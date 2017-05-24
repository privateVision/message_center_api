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
            'subInfo'=>json_encode($userSubs, JSON_FORCE_OBJECT),
            'total_fee'=>$total_fee,
        ];
    }
}
