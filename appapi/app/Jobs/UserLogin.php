<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Mail;

class SendMail extends Job
{

    public function __construct() {

    }

    public function handle() {
        $user->last_login_at = datetime();
        $user->last_login_ip = getClientIp();
        $user->save();

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

        $ucuser_login = UcuserLogin::find($user->ucid);
        if($ucuser_login) {
            // 地点和设备都不同，异地登陆提醒
            if(($ip2location && $ucuser_login->city_id && $ucuser_login->city_id != $ip2location->city_id) && ($device_id && $ucuser_login->device_id && $ucuser_login->device_id != $device_id)) {
                if($user->mobile) {
                    sendsms($user->mobile, $pid, 'account_abnormal', [
                        'username' => $user->uid,
                        'month' => date('m', $t),
                        'day' => date('d', $t),
                        'time' => date('H:i:s', $t),
                    ]);
                }
            }
            // 设备不同，连续三次都是这个设备，更改常用设备
            elseif ($device_id && $ucuser_login->last_device_id != $device_id) {
                $is_commonly = true;
                $ucuser_login_log = UcuserLoginLog::where('ucid', $user->ucid)->orderBy('ts', 'desc')->limit(3)->get();
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
    }
}