<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\User;
use App\Model\UserSubService;
use App\Model\UserSub;
use App\Model\Session;
use App\Model\LoginLog;

trait RegisterAction {
    
    public function RegisterAction(Request $request, Parameter $parameter){
        $pid = $parameter->tough('_appid');
        $rid = $parameter->tough('_rid');
        
        $user = $this->getRegisterUser($request, $parameter);
        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号被冻结，无法登陆');
        }

        // 查找最近一次登陆的小号
        $user_sub = UserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();

        // 用户没有可用的小号，创建
        if(!$user_sub) {
            $user_sub = UserSub::tableSlice($user->ucid);
            $user_sub->id = uuid($user->ucid);
            $user_sub->ucid = $user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $user->ucid;
            $user_sub->name = '小号01';
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
            $user_sub->save();
        }

        $user_sub->priority = time();
        $user_sub->last_login_at = datetime();
        $user_sub->save();

        $session = new Session;
        $session->pid = $pid;
        $session->rid = $rid;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->cp_uid = $user_sub->cp_uid;
        $session->token = uuid($user->ucid);
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();
        
        $user->uuid = $session->token; // todo: 兼容旧的自动登陆
        $user->last_login_at = datetime();
        $user->save();
        
        $login_log = new LoginLog;
        $login_log->ucid = $user->ucid;
        $login_log->pid = $pid;
        $login_log->loginDate = intval(time() / 86400);
        $login_log->loginTime = time() % 86400;
        $login_log->loginIP = ip2long($request->ip());
        $login_log->asyncSave();

        return [
            'openid' => strval($user_sub->cp_uid),
            'sub_nickname' => strval($user_sub->name),
            'uid' => $user->ucid,
            'username' => $user->uid,
            'nickname' => $user->nickname,
            'mobile' => strval($user->mobile),
            'avatar' => $user->avatar,
            'is_real' => $user->isReal(),
            'is_adult' => $user->isAdult(),
            'vip' => $user->vip,
            'token' => $session->token,
            'balance' => $user->balance,
        ];
    }

    abstract public function getRegisterUser(Request $request, Parameter $parameter);

}