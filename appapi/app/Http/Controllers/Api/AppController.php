<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\ProceduresExtend;

class AppController extends Controller
{
    public function InitializeAction() {
        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        $device_id = $this->parameter->tough('_device_id');
        $app_version = $this->parameter->tough('app_version');

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

        $oauth_params = sprintf('appid=%d&rid=%d&device_id=%s', $pid, $rid, $device_id);
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
}
