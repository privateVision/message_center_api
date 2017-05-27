<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Session;
use App\Model\Ucuser;
use App\Model\UcuserSubService;
use App\Model\UcuserSub;
use App\Model\UcuserSession;
use App\Model\LoginLog;
use App\Model\UcuserInfo;
use App\Model\Retailers;
use App\Model\LoginLogUUID;

trait LoginAction {

    public function LoginAction() {
        $pid = $this->procedur->pid;
        $rid = $this->parameter->tough('_rid');
        
        $user = $this->getLoginUser();
        if($user && $user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze_onlogin'), ['ucid' => $user->ucid]);
        }

        $user_sub_id = $this->getDefaultUserSubId($user);

        $user_sub = null;
        if($user_sub_id) {
            $user_sub = UcuserSub::tableSlice($user->ucid)->from_cache($user_sub_id);
            if(!$user_sub || $user_sub->ucid != $user->ucid || $user_sub->pid != $pid) {
                throw new ApiException(ApiException::Remind, trans('messages.role_not_exists'));
            }

            if($user_sub->is_freeze) {
                throw new ApiException(ApiException::UserSubFreeze, trans('messages.role_freeze_onlogin'));
            }
        }

        $is_service = false;
        
        if(!$user_sub) {
            // 客服登录用户的小号
            $user_sub_service = UcuserSubService::where('ucid', $user->ucid)->where('pid', $pid)->where('status', UcuserSubService::Status_Normal)->orderBy('id', 'desc')->first();
            if($user_sub_service) {
                $user_sub = UcuserSub::tableSlice($user_sub_service->src_ucid)->from_cache($user_sub_service->user_sub_id);
                $is_service = $user_sub != null;
            }

            // 查找最近一次登录的小号
            if(!$user_sub) {
                $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();
            }

            // 用户没有可用的小号，创建
            if(!$user_sub) {
                // XXX 在涉及到出小号的情况下，一个小号出售给别人openid是不变的，再给卖家创建一个同openid的小号是有问题的
                // 如果判断到用户没有小号的情况下给用户自动创建的第一个小号的openid不再有规律
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
        }

        if(!$is_service) {
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
            $user_sub->save();
        }

        // session
        $session = new Session(uuid($user->ucid));
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
        
        //$user->uuid = $session->token;
        $user->last_login_at = datetime();
        $user->last_login_ip = getClientIp();
        $user->save();
        $user->updateCache();

        $t = time();

        $login_log = new LoginLog;
        $login_log->ucid = $user->ucid;
        $login_log->pid = $pid;
        $login_log->loginDate = intval(($t - date('Z'))/ 86400);
        $login_log->loginTime = $t % 86400;
        $login_log->loginIP = ip2long(getClientIp());
        $login_log->save();
        
        // login_log_uuid
        $imei = $this->parameter->get('_imei', '');
        $device_id = $this->parameter->get('_device_id', '');
        if($imei || $device_id) {
            $login_log_uuid = new LoginLogUUID;
            $login_log_uuid->id = $login_log->id;
            $login_log_uuid->ucid = $user->ucid;
            $login_log_uuid->imei = $imei;
            $login_log_uuid->device_id= $device_id;
            $login_log_uuid->asyncSave();
        }

        $user_info = UcuserInfo::from_cache($user->ucid);
        
        $retailers = null;
        if($user->rid) {
            $retailers = Retailers::find($user->rid);
        }

        return [
            'openid' => strval($user_sub->cp_uid),
            'sub_nickname' => strval($user_sub->name),
            'uid' => $user->ucid,
            'username' => $user->uid,
            'nickname' => $user->nickname ? $user->nickname : '',
            'mobile' => strval($user->mobile),
            'avatar' => $user_info && $user_info->avatar ? (string)$user_info->avatar : env('default_avatar'),
            'is_real' => $user_info && $user_info->isReal(),
            'is_adult' => $user_info && $user_info->isAdult(),
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'token' => $session->token,
            'balance' => $user->balance,
            'real_name' => $user_info && $user_info->real_name ? (string)$user_info->real_name : "",
            'card_no' => $user_info && $user_info->card_no ? (string)$user_info->card_no : "",
            'regtype' => $user->regtype,
            'rid' => $user->rid,
            'rtype' => $retailers ? $retailers->rtype : 0,
        ];
    }

    /**
     * 获取登录的用户
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