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

        $default = array(
            'service_qq' => env('SERVICE_QQ'),
            'service_page' => '',
            'service_phone' => env('SERVICE_PHONE'),
            'service_share' => env('SERVICE_SHARE'),
            'service_interval' => 300,
            'bind_phone_need' => true,
            'bind_phone_enforce' => false,
            'time_interval' => 86400,
            'real_name_need' => false,
            'real_name_enforce' => false,
        );

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

        $data['access_token'] = $session->access_token;

        // config
        $extend = $this->procedure->procedures_extend()->first();
        if($extend) {
            foreach($default as $k => $v) {
                $data[$k] = ($extend->$k !== null && $extend->$k !== '') ? $extend->$k : $v;
            }
        }

        // check update
        $update = $this->procedure->update_apks()->orderBy('dt', 'desc')->first();
        if($update && $update->version != $app_version) {
            $data['update'] = array(
                'down_url' => $update->down_uri,
                'version' => $update->version,
                'force_update' => $update->force_update,
            );
        } else {
            $data['update'] = null;
        }
        
        return $data;
    }
}
