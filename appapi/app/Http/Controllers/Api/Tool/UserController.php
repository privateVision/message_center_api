<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\Procedures;
use Illuminate\Http\Request;
use App\Session;
use App\Model\Orders;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\UcuserSession;
use App\Model\TotalFeePerUser;
use App\Model\UcuserSub;
use App\Model\UcuserRole;

class UserController extends AuthController
{
    public function ResetPasswordPageAction() {
        $token = encrypt3des(json_encode(['ucid' => $this->user->ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= http_build_query([
            'token' => $token,
            'username' => $this->user->uid
        ]);

        return ['url' => $url, 'token' => $token];
    }

    public function FreezeAction() {
        $status = $this->parameter->tough('status');
        $admin_user = $this->parameter->tough('admin_user');

        $is_freeze = $status > 0 ? true : false;
        if($is_freeze) {
            $comment = $this->parameter->tough('comment');
        }

        $this->user->is_freeze = $is_freeze;
        $this->user->save();

        if($is_freeze) {
            user_log($this->user, $this->procedure, 'freeze', '【冻结账号】%s，由%s操作', $comment, $admin_user);
        } else {
            user_log($this->user, $this->procedure, 'unfreeze', '【解冻账号】由%s操作', $admin_user);
        }

        $usession = UcuserSession::where('ucid', $this->user->ucid)->get();
        foreach($usession as $v) {
            $s = Session::find($v->session_token);
            if($s) {
                $s->freeze = $is_freeze ? 1 : 0;
                $s->save();
            }
        }

        return ['result' => true];
    }

    //好充账号验证
    public function AuthAccountAction()
    {
        $username = $this->parameter->tough('username');
        $password = $this->parameter->tough('password');
        $gameCenterId = $this->parameter->tough('gameCenterId');

        $user = Ucuser::where('mobile', $username)->orWhere('uid', $username)->orWhere('email', $username)->first();

        if(!$user||!$user->checkPassword($password)){
            throw new ApiException(ApiException::Remind, trans('messages.login_fail'));
        }

        if($user->getIsFreezeAttribute){
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze'));
        }

        $pids = Procedures::where('gameCenterId', $gameCenterId)->get('pid')->toArray();

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
            'sub'=>$userSubs,
            'total_fee'=>$total_fee,
        ];

    }
}