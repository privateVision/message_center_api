<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\ProceduresExtend;
use App\Model\Log\DeviceApps;
use App\Model\Log\DeviceInfo;

class AppController extends Controller
{
    public function InitializeAction() {
        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        $imei = $this->parameter->tough('_imei');
        $uuid = $this->parameter->tough('_device_id');
        $apps = $this->parameter->tough('device_apps');
        $info = $this->parameter->tough('device_info');
        $app_version = $this->parameter->tough('app_version');

        $_apps = json_decode($apps, true);
        if($_apps) {
            $device_apps = new DeviceApps;
            $device_apps->imei = $imei;
            $device_apps->uuid = $uuid;
            $device_apps->apps = $_apps;
            $device_apps->save();
        } else {
            log_error('report_device_apps_parse_error', null, '上报的DeviceApps格式无法解析');
        }

        $_info = json_decode($info, true);
        if($_info) {
            $device_info = new DeviceInfo;
            $device_info->imei = $imei;
            $device_info->uuid = $uuid;
            $device_info->info = $_info;
            $device_info->save();
        } else {
            log_error('report_device_info_parse_error', null, '上报的DeviceInfo格式无法解析');
        }

        // config
        $config = ProceduresExtend::from_cache($pid);
        if(!$config) {
            $config = new ProceduresExtend;
            $config->pid = $pid;
            $config->service_qq = env('service_qq');
            $config->service_page = env('service_page');
            $config->service_phone = env('service_phone');
            $config->service_share = env('service_share');
            $config->heartbeat_interval = 2000;
            $config->bind_phone_need = true;
            $config->bind_phone_enforce = false;
            $config->bind_phone_interval = 86400000;
            $config->real_name_need = false;
            $config->real_name_enforce = false;
            $config->logout_img = env('logout_img');
            $config->logout_redirect = env('logout_redirect');
            $config->logout_inside = true;
            $config->allow_num = 1;
            $config->create_time = time();
            $config->update_time = time();
            $config->saveAndCache();
        }

        // check update
        $update = new \stdClass;
        $update_apks = $this->procedure->update_apks()->orderBy('dt', 'desc')->first();
        if($update_apks && $update_apks->version != $app_version) {
            $update = array(
                'down_url' => $update_apks->down_uri,
                'version' => $update_apks->version,
                'force_update' => $update_apks->force_update,
            );
        }

        $oauth_params = sprintf('appid=%d&rid=%d&device_id=%s', $pid, $rid, $uuid);
        $oauth_qq = env('oauth_url_qq');
        $oauth_qq .= (strpos($oauth_qq, '?') === false ? '?' : '&') . $oauth_params;
        $oauth_weixin = env('oauth_url_weixin');
        $oauth_weixin .= (strpos($oauth_weixin, '?') === false ? '?' : '&') . $oauth_params;
        $oauth_weibo = env('oauth_url_weibo');
        $oauth_weibo .= (strpos($oauth_weibo, '?') === false ? '?' : '&') . $oauth_params;

        return [
            'allow_sub_num' => $config->allow_num,
            'oauth_login' => [
                'qq' => [
                    'url' => $oauth_qq,
                ],
                'weixin' => [
                    'url' => $oauth_weixin,
                ],
                'weibo' => [
                    'url' => $oauth_weibo,
                ]
            ],
            'protocol' => [
                'title' => env('protocol_title'),
                'url' => env('protocol_url'),
            ],
            'update' => $update,
            'service' => [
                'qq' => $config->service_qq,
                'page' => $config->service_page,
                'phone' => $config->service_phone,
                'share' => $config->service_share,
                'interval' => $config->heartbeat_interval,
            ],
            'bind_phone' => [
                'need' => $config->bind_phone_need,
                'enforce' => $config->bind_phone_enforce,
                'interval' => $config->bind_phone_interval,
            ],
            'real_name' => [
                'need' => $config->real_name_need,
                'enforce' => $config->real_name_enforce,
            ]
        ];
    }

    public function LogoutAction() {
        $procedures_extend = ProceduresExtend::from_cache($this->procedure->pid);
        return [
            'img' => $procedures_extend->logout_img,
            'type' => $procedures_extend->logout_type,
            'redirect' => $procedures_extend->logout_redirect,
            'inside' => $procedures_extend->logout_inside,
        ];
    }

    public function VerifySMSAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        return ['result' => true];
    }

    public function UuidAction() {
        return ['uuid' => uuid()];
    }
/*
    public function ReportAppsAction() {
        $imei = $this->parameter->tough('imei');
        $uuid = $this->parameter->tough('_device_id');
        $apps = $this->parameter->tough('apps');

        $device_apps = new DeviceApps;
        $device_apps->imei = $imei;
        $device_apps->uuid = $uuid;
        $device_apps->apps = $apps;
        $device_apps->save();

        return ['result' => true];
    }

    public function ReportDeviceInfoAction() {
        $imei = $this->parameter->tough('imei');
        $uuid = $this->parameter->tough('_device_id');

        $device_info = new DeviceInfo;
        $device_info->imei = $imei;
        $device_info->uuid = $uuid;
        $device_info->save();

        return ['result' => true];
    }
*/
}
