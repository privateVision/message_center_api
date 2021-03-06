<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\YunpianCallback;
use App\Model\UcusersUUID;

class OnekeyController extends Controller {

    use LoginAction;

    const Type = 4;

    public function getLoginUser() {
        $sms_token = $this->parameter->tough('sms_token');

        $yunpian_callback = YunpianCallback::where('text', $sms_token)->first();

        if(!$yunpian_callback) {
            throw new ApiException(ApiException::MobileNotRegister, trans('messages.not_accept_sms'));
        }

        $mobile = $yunpian_callback->mobile;

        // 登录
        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            return $user;
        }
        
        // 注册
        $username = username();
        $password = rand(100000, 999999);

        //平台注册账号
        $user = self::baseRegisterUser([
            'uid' => $username,
            'password' => $password
        ]);

        user_log($user, $this->procedure, 'register', '【注册】通过“手机号码一键登录”注册，手机号码{%s}, 密码[%s]', $mobile, $user->password);

        try {
            send_sms($mobile, 0, 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }
        
        return $user;
    }
    
    public function SMSTokenAction() {
        $config = configex('common.smsconfig');
        return [
            'sms_token' => uuid(), 
            'send_to' => $config['receiver']
        ];
    }
}