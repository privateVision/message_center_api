<?php
namespace App\Http\Controllers\Tool;

use App\Exceptions\ToolException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\SMSRecord;

class SMSController extends Controller {

    public function SendAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile');
        $template_id = $parameter->tough('template_id');
        $replace = $parameter->get('replace');
        $code = $parameter->get('code');

        try {
            $content = send_sms($mobile, $this->app, $template_id, $replace, $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ToolException(ToolException::Remind, $e->getMessage());
        }

        return ['result' => true, 'uid' => 0, 'content' => $content];
    }

    public function VerifyAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile');
        $code = $parameter->tough('code');

        if(!verify_sms($mobile, $code)) {
            throw new ToolException(ToolException::Remind, "验证码不正确，或已过期");
        }

        return ['result' => true];
    }
}