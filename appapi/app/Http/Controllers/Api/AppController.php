<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Model\SMSRecord;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
        $app_version = $parameter->tough('app_version');

        // config
        $config = $this->procedure->procedures_extend()->first();

        // check update
        $update = [];
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
                'update' => $update,
                'service' => [
                    'qq' => strval($config->service_qq),
                    'page' => strval($config->service_page),
                    'phone' => strval($config->service_phone),
                    'share' => strval($config->service_share),
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
                'update' => $update,
                'service' => [
                    'qq' => env('SERVICE_QQ'),
                    'page' => env('SERVICE_PAGE'),
                    'phone' => env('SERVICE_PHONE'),
                    'share' => env('SERVICE_SHARE'),
                    'interval' => 300000,
                ],
                'bind_phone' => [
                    'need' => true,
                    'enforce' => false,
                    'interval' => 86400000,
                ],
                'real_name' => [
                    'need' => false,
                    'enforce' => false,
                ]
            ];
        }
    }

    public function VerifySMSAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile');
        $code = $parameter->tough('code');

        $SMSRecord = SMSRecord::verifyCode($mobile, $code);

        if(!$SMSRecord) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        return ['result' => true];
    }
}
