<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Exceptions\ApiException;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
        $imei = $parameter->tough('imei');
        $rid = $parameter->tough('rid');
        $device_code = $parameter->tough('device_code');
        $device_name = $parameter->tough('device_name');
        $device_platform = $parameter->tough('device_platform');
        $version = $parameter->tough('version');
        $app_version = $parameter->tough('app_version');

        // token
        $session = new Session;
        $session->access_token = uuid();
        $session->pid = $this->procedure->pid;
        $session->imei = $imei;
        $session->rid = $rid;
        $session->device_code = $device_code;
        $session->device_name = $device_name;
        $session->device_platform = $device_platform;
        $session->version = $version;
        $session->device_code = $device_code;
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();

        $access_token = $session->access_token;

        // config
        $config = $this->procedure->procedures_extend()->first();

        // check update
        $update = null;
        $update_apks = $this->procedure->update_apks()->orderBy('dt', 'desc')->first();
        if($update_apks && $update_apks->version != $app_version) {
            $update = array(
                'down_url' => $update_apks->down_uri,
                'version' => $update_apks->version,
                'force_update' => $update_apks->force_update,
            );
        }

        if($config) {
            return [
                'access_token' => $session->access_token,
                'update' => $update,
                'service' => [
                    'qq' => $config->service_qq,
                    'page' => $config->service_page,
                    'phone' => $config->service_phone,
                    'share' => $config->service_share,
                    'interval' => $config->service_interval * 1000,
                ],
                'bind_phone' => [
                    'need' => $config->bind_phone_need,
                    'enforce' => $config->bind_phone_enforce,
                    'interval' => $config->time_interval * 1000,
                ],
                'real_name' => [
                    'need' => $config->real_name_need,
                    'enforce' => $config->real_name_enforce,
                ]
            ];
        } else {
            return [
                'access_token' => $session->access_token,
                'update' => $update,
                'service' => [
                    'qq' => env('SERVICE_QQ'),
                    'page' => env('SERVICE_PAGE'),
                    'phone' => env('SERVICE_PHONE'),
                    'share' => env('SERVICE_SHARE'),
                    'interval' => 300000,
                ],
                'bind_phone' => [
                    'need' => 1,
                    'enforce' => 0,
                    'interval' => 86400000,
                ],
                'real_name' => [
                    'need' => 0,
                    'enforce' => 0,
                ]
            ];
        }
    }
}
