<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Session;
use App\Model\Ucuser;
use App\Model\UcuserSub;
use App\Model\LoginLog;
use App\Model\UcuserInfo;
use App\Model\UcuserSession;

trait RegisterAction {
    
    public function RegisterAction(){

        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        
        $user = $this->getRegisterUser();
        if(!$user) throw new ApiException(ApiException::OauthNotRegister, '未注册第三方账号，请注册');
        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号被冻结，无法登录');
        }

        // 查找最近一次登录的小号
        $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();

        // 用户没有可用的小号，创建
        if(!$user_sub) {
            $user_sub = UcuserSub::tableSlice($user->ucid);
            $user_sub->id = $user->ucid . sprintf('%05d01', $pid);
            $user_sub->ucid = $user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $user->ucid;
            $user_sub->name = '小号1';
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
        }

        $user_sub->priority = time();
        $user_sub->last_login_at = datetime();
        $user_sub->save();

        // session
        $session = new Session(joinkey($user->ucid, $pid, $rid, $user_sub->id));
        $session->pid = $pid;
        $session->rid = $rid;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->user_sub_name = $user_sub->name;
        $session->cp_uid = $user_sub->cp_uid;
        $session->save();

        log_debug('session', ['ucid' => $user->ucid, 'pid' => $pid, 'at' => microtime(true), 'token' => $session->token]);

        // ucuser_session
        $usession_uuid = joinkey($user->ucid, min($pid, 100));
        $usession = UcuserSession::from_cache_uuid($usession_uuid);
        if(!$usession) { 
            $usession = new UcuserSession;
            $usession->uuid = $usession_uuid;
            $usession->type = min($pid, 100);
            $usession->ucid = $user->ucid;
        }
        $usession->session_token = $session->token;
        $usession->saveAndCache();
        
        // ucuser
        $user->uuid = $session->token;
        $user->last_login_at = datetime();
        $user->last_login_ip = $this->request->ip();
        $user->save();
        $user->updateCache();
        
        // login_log
        $login_log = new LoginLog;
        $login_log->ucid = $user->ucid;
        $login_log->pid = $pid;
        $login_log->loginDate = intval(time() / 86400);
        $login_log->loginTime = time() % 86400;
        $login_log->loginIP = ip2long($this->request->ip());
        $login_log->asyncSave();

        $user_info = UcuserInfo::from_cache($user->ucid);

        return [
            'openid' => strval($user_sub->cp_uid),
            'sub_nickname' => strval($user_sub->name),
            'uid' => $user->ucid,
            'username' => $user->uid,
            'nickname' => $user->nickname,
            'mobile' => strval($user->mobile),
            'avatar' => $user_info && $user_info->avatar ? (string)$user_info->avatar : env('default_avatar'),
            'is_real' => $user_info && $user_info->isReal(),
            'is_adult' => $user_info && $user_info->isAdult(),
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'token' => $session->token,
            'balance' => $user->balance,
            'real_name' => $user_info && $user_info->real_name ? (string)$user_info->real_name : "",
            'card_no' => $user_info && $user_info->card_no ? (string)$user_info->card_no : "",
        ];
    }

    abstract public function getRegisterUser(Ucuser $user);
}