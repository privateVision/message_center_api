<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use App\Jobs\AdtRequest;
use App\Session;
use App\Model\Ucuser;
use App\Model\UcuserSub;
use App\Model\LoginLog;
use App\Model\UcuserInfo;
use App\Model\UcuserSession;
use App\Model\Retailers;
use App\Model\LoginLogUUID;
use App\Model\UcuserSubTotal;

trait RegisterAction {
    
    public function RegisterAction(){
        $pid = $this->procedure->pid;
        $rid = $this->parameter->tough('_rid');
        
        $user = $this->getRegisterUser();

        // 广告统计，加入另一个队列由其它项目处理
        $imei = $this->parameter->get('_imei');
        if($imei) {
            dispatch((new AdtRequest([
                'imei' => $imei,
                'gameid' => $pid,
                'rid'=>$rid,
                'ucid' => $user->uid
            ]))->onQueue('adtinit'));
        }

        //if(!$user) throw new ApiException(ApiException::OauthNotRegister, trans('messages.3th_not_register'));
        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze'));
        }

        // 查找最近一次登录的小号
        $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();

        // 用户没有可用的小号，创建
        if(!$user_sub) {
            $user_sub_total_id = joinkey($pid, $user->ucid);
            $user_sub_total = UcuserSubTotal::find($user_sub_total_id);
            if(!$user_sub_total) {
                $user_sub_total = new UcuserSubTotal;
                $user_sub_total->id = $user_sub_total_id;
                $user_sub_total->pid = $pid;
                $user_sub_total->ucid = $user->ucid;
                $user_sub_total->total = 1;
                $user_sub_total->save();
            } else {
                $user_sub_total->increment('total', 1);
            }

            $user_sub = UcuserSub::tableSlice($user->ucid);
            $user_sub->id = $user->ucid . sprintf('%05d01', $pid);
            $user_sub->ucid = $user->ucid;
            $user_sub->pid = $pid;
            $user_sub->rid = $rid;
            $user_sub->old_rid = $rid;
            $user_sub->cp_uid = $user_sub_total->total == 1 ? $user->ucid : ($user->ucid . sprintf('%05d%02d', $pid, $user_sub_total->total));
            $user_sub->name = '小号' . $user_sub_total->total;
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
        }

        $user_sub->priority = time();
        $user_sub->last_login_at = datetime();
        $user_sub->save();

        // session
        $session = new Session(uuid($user->ucid));
        $session->pid = $pid;
        $session->rid = $rid;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->user_sub_name = $user_sub->name;
        $session->cp_uid = $user_sub->cp_uid;
        $session->save();
        
        log_debug('session', ['ucid' => $user->ucid, 'pid' => $pid, 'at' => microtime(true), 'path' => $this->request->path()], $session->token);

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
        // $user->uuid = $session->token;
        $user->last_login_at = datetime();
        $user->last_login_ip = getClientIp();
        $user->save();
        $user->updateCache();
        
        // login_log
        $t = time();
        
        $login_log = new LoginLog;
        $login_log->ucid = $user->ucid;
        $login_log->pid = $pid;
        /**
         * XXX 兼容旧的问题，后台显示是强制
         * SELECT id,loginDate,loginTime,FROM_UNIXTIME(
         *   CASE
         *     WHEN loginTime < 57600 THEN (loginDate+1)*86400+loginTime
         *     ELSE loginDate*86400+loginTime
         *   END + 8*3600
         * ) AS stamp FROM login_log_161013
         */
        $login_log->loginDate = intval(($t + 28800) / 86400) - 1;
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
            'regtype' => $user->regtype,
            'rid' => $user->rid,
            'rtype' => $retailers ? $retailers->rtype : 0,
        ];
    }

    abstract public function getRegisterUser(Ucuser $user);
}