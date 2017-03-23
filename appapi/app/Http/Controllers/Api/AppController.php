<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\ProceduresExtend;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
        $pid = $parameter->tough('_appid');
        $app_version = $parameter->tough('app_version');

        // config
        $config = ProceduresExtend::from_cache($pid);
        if(!$config) {
            $config = new ProceduresExtend;
            $config->pid = $pid;
            $config->save();

            $config = ProceduresExtend::from_cache($pid);
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

        return [
            'allow_sub_num' => $config->allow_num,
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
    }

    public function VerifySMSAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        return ['result' => true];
    }

    public function UuidAction(Request $request, Parameter $parameter) {
        return ['uuid' => uuid()];
    }

    public function UseProtocolAction(Request $request, Parameter $parameter) {
        
    }
}
