<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use App\Jobs\AdtRequest;
use App\Session;
use App\Model\Ucuser;
use App\Model\UcuserSub;
use App\Model\UcuserLoginLog;
use App\Model\UcuserInfo;
use App\Model\UcuserSession;
use App\Model\Retailers;
use App\Model\UcuserSubTotal;
use App\Model\UcuserLogin;

trait RegisterAction {
    
    public function RegisterAction(){
        $pid = $this->procedure->pid;
        $rid = $this->parameter->tough('_rid');

        $imei = $this->parameter->get('_imei', '');
        if($pid >= 100 && version_compare($this->parameter->get('_version'), '4.2', '>=')) {
            $device_id = $this->parameter->tough('_device_id');
        } else {
            $device_id = $this->parameter->get('_device_id', '');
        }
        
        $user = $this->getRegisterUser();
        $user->asyncSave();

        // 广告统计，加入另一个队列由其它项目处理
        if($imei) {
            dispatch((new AdtRequest([
                'imei' => $imei,
                'gameid' => $pid,
                'rid'=>$rid,
                'ucid' => $user->uid
            ]))->onQueue('adtinit'));
        }

        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze'));
        }

        // 查找最近一次登录的小号
        $user_sub = UcuserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();

        // XXX 用户没有可用的小号
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
        $user->last_login_at = datetime();
        $user->last_login_ip = getClientIp();
        
        // login_log
        $t = time();
        $ip2location = \App\Model\IP2Location::find(getClientIp());

        $ucuser_login_log = new UcuserLoginLog;
        $ucuser_login_log->ucid = $user->ucid;
        $ucuser_login_log->pid = $pid;
        $ucuser_login_log->loginDate = intval(($t + 28800) / 86400) - 1;
        $ucuser_login_log->loginTime = $t % 86400;
        $ucuser_login_log->loginIP = ip2long(getClientIp());
        $ucuser_login_log->date = date('Ymd', $t);
        $ucuser_login_log->ts = $t;
        $ucuser_login_log->ip = getClientIp();
        $ucuser_login_log->address = $ip2location ? ($ip2location->region . $ip2location->city . $ip2location->county . $ip2location->isp) : null;
        $ucuser_login_log->city_id = $ip2location ? $ip2location->city_id : null;
        $ucuser_login_log->imei = $imei;
        $ucuser_login_log->device_id = $device_id;
        $ucuser_login_log->save();

        $ucuser_login = UcuserLogin::find('ucid', $user->ucid);
        if($ucuser_login) {
            // 地点和设备都不同，异地登陆提醒
            if(($ip2location && $ucuser_login->city_id != $ip2location->city_id) && ($device_id && $ucuser_login->device_id != $device_id)) {
                //$ucuser_login->last_city_id = $ip2location ? $ip2location->city_id : '';
                //$ucuser_login->last_device_id = $device_id;
                //$ucuser_login->save();
            }
            // 设备不同，连续三次都是这个设备，更改常用设备
            elseif ($device_id && $ucuser_login->last_device_id != $device_id) {
                $is_commonly = true;
                $ucuser_login_log = UcuserLoginLog::where('ucid', $user->ucid)->orderBy('ts', desc)->limit(3)->get();
                foreach($ucuser_login_log as $v) {
                    if($v->device_id != $device_id) {
                        $is_commonly = false;
                        break;
                    }
                }

                if($is_commonly) {
                    $ucuser_login->device_id = $device_id;
                    $ucuser_login->save();
                }
            }
            // 登陆地点不同，连续三天都是这个地址，更改常用地址
            elseif($ip2location && $ucuser_login->city_id != $ip2location->city_id) {
                $day3 = [
                    date('Ymd', $t),
                    date('Ymd', $t - 86400),
                    date('Ymd', $t - 172800),
                ];

                $count = UcuserLoginLog::where('city_id', '!=', $ip2location->city_id)->whereIn('date', $day3)->count();
                if($count == 0) {
                    $ucuser_login->city_id = $ip2location->city_id;
                    $ucuser_login->save();
                }
            }
        } else {
            $ucuser_login = new UcuserLogin;
            $ucuser_login->ucid = $user->ucid;
            $ucuser_login->city_id = $ip2location ? $ip2location->city_id : '';
            $ucuser_login->device_id = $device_id;
            $ucuser_login->save();
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

    abstract public function getRegisterUser(Ucuser $user);
}