<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\UcuserSubService;
use App\Model\UcuserSub;
use App\Model\Session;
use App\Model\LoginLog;
use App\Model\UcuserInfo;

trait LoginAction {

    public function LoginAction() {
        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        
        $user = $this->getLoginUser();
        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登陆');
        }
        
        $user_sub_id = $this->getDefaultUserSubId($user);

        $user_sub = null;
        if($user_sub_id) {
            $user_sub = UcuserSub::tableSlice($user->ucid)->from_cache($user_sub_id);
            if(!$user_sub || $user_sub->ucid != $user->ucid || $user_sub->pid != $pid) {
                throw new ApiException(ApiException::Remind, "角色不存在，无法登陆");
            }
            
            if($user_sub->is_freeze) {
                throw new ApiException(ApiException::UserSubFreeze, '子账号已被冻结，无法登陆');
            }
        }

        $is_service = false;
        
        if(!$user_sub) {
            // 客服登陆用户的小号
            $user_sub_service = UcuserSubService::where('ucid', $user->ucid)->where('pid', $pid)->where('status', UcuserSubService::Status_Normal)->orderBy('id', 'desc')->first();
            if($user_sub_service) {
                $user_sub = UcuserSub::tableSlice($user_sub_service->src_ucid)->from_cache($user_sub_service->user_sub_id);
            }

            // 查找最近一次登陆的小号
            if(!$user_sub) {
                $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();
                $is_service = true;
            }

            // 用户没有可用的小号，创建
            if(!$user_sub) {
                $user_sub = UcuserSub::tableSlice($user->ucid);
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
        }

        if(!$is_service) {
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
            $user_sub->save();
        }

        $session = new Session;
        $session->pid = $pid;
        $session->rid = $rid;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->user_sub_name = $user_sub->name;
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
        ];
    }

    /**
     * 获取登陆的用户
     * @return [type]               [description]
     */
    abstract public function getLoginUser();

    /**
     * [getDefaultUserSubId description]
     * @param  Ucuser $user [description]
     * @return [type]       [description]
     */
    public function getDefaultUserSubId(Ucuser $user) {
        return null;
    }
}