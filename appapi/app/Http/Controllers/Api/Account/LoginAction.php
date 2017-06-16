<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use App\Jobs\AdtRequest;
use App\Session;
use App\Model\Ucuser;
use App\Model\UcuserSubService;
use App\Model\UcuserSub;
use App\Model\UcuserSession;
use App\Model\UcuserLoginLog;
use App\Model\UcuserInfo;
use App\Model\Retailers;
use App\Model\UcuserSubTotal;

trait LoginAction {

    public function LoginAction() {
        $pid = $this->procedure->pid;
        $rid = $this->parameter->tough('_rid');
        
        $user = $this->getLoginUser();
        $user->asyncSave();

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

        if($user->is_freeze === Ucuser::IsFreeze_Freeze) { // 冻结
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze_onlogin'), ['ucid' => $user->ucid]);
        }

        // SDK2.0 没有插入 last_login_at
        if($user->last_login_at) {
            $last_login_at = strtotime($user->last_login_at);
            if($last_login_at && (time() - $last_login_at) >= 15552000) { // 半年未登陆，设为异常
                $user->is_freeze = Ucuser::IsFreeze_Abnormal;
                user_log($user, $this->procedure, 'abnormal', '【帐号异常】长时间未登陆');
            }
        }

        if($user->is_freeze === Ucuser::IsFreeze_Abnormal) { // 异常
            throw new ApiException(ApiException::AccountAbnormal, trans('messages.abnormal_onlogin'), ['ucid' => $user->ucid]);
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
                $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', 0)->orderBy('priority', 'desc')->first();
            }

            // 用户没有可用的小号，创建
            if(!$user_sub) {
                $user_sub_total_id = joinkey($pid, $user->ucid);
                $user_sub_total = UcuserSubTotal::find($user_sub_total_id);
                if(!$user_sub_total) {
                    $user_sub_total = new UcuserSubTotal;
                    $user_sub_total->id = $user_sub_total_id;
                    $user_sub_total->pid = $pid;
                    $user_sub_total->ucid = $user->ucid;
                    $user_sub_total->total = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->count() + 1;
                    $user_sub_total->save();
                } else {
                    $user_sub_total->increment('total', 1);
                }

                $user_sub_id = sprintf('%d%05d%02d', $user->ucid, $pid, $user_sub_total->total);

                $user_sub = UcuserSub::tableSlice($user->ucid);
                $user_sub->id = $user_sub_id;
                $user_sub->ucid = $user->ucid;
                $user_sub->pid = $pid;
                $user_sub->rid = $rid;
                $user_sub->old_rid = $rid;
                $user_sub->cp_uid = $user_sub_total->total == 1 ? $user->ucid : $user_sub_id;
                $user_sub->name = '小号' . $user_sub_total->total;
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

        $user->last_login_at = datetime();
        $user->last_login_ip = getClientIp();
        $user->save();

        $t = time();

        $ucuser_login_log = new UcuserLoginLog;
        $ucuser_login_log->ucid = $user->ucid;
        $ucuser_login_log->pid = $pid;
        $ucuser_login_log->loginDate = intval(($t + 28800) / 86400) - 1;
        $ucuser_login_log->loginTime = $t % 86400;
        $ucuser_login_log->loginIP = ip2long(getClientIp());
        $ucuser_login_log->date = date('Ymd', $t);
        $ucuser_login_log->ts = $t;
        $ucuser_login_log->ip = getClientIp();
        $ucuser_login_log->address =
        $ucuser_login_log->imei = $this->parameter->get('_imei', '');
        $ucuser_login_log->device_id = $this->parameter->get('_device_id', '');
        $ucuser_login_log->save();

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
            'avatar' => $user_info && $user_info->avatar ? httpsurl((string)$user_info->avatar) : httpsurl(env('default_avatar')),
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