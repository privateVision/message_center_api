<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;

class MobileController extends Controller {

    use LoginAction;

    const Type = 2;

    public function getLoginUser() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');
        $password = $this->parameter->get('password', null, 'password'); // 在注册时有用，用户如果设置了密码则系统不再为其生成密码

        $is_set_password = !empty($password);

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        // 登录
        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($is_set_password) $user->setPassword($password);
            return $user;
        }

        // 注册
        $username = username();
        if(!$is_set_password) {
            $password = rand(100000, 999999);
        }

        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = $mobile;
        $user->nickname = '暂无昵称';
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->imei = $this->parameter->get('_imei', '');
        $user->device_id = $this->parameter->get('_device_id', '');
        $user->save();

        user_log($user, $this->procedure, 'register', '【手机号码登录】检测到尚未注册，手机号码{%s}，密码[%s]', $mobile, $user->password);

        if(!$is_set_password) {
            // 将密码发给用户，通过队列异步发送
            try {
                send_sms($mobile, 0, 'mobile_register', ['#username#' => $username, '#password#' => $password]);
            } catch (\App\Exceptions\Exception $e) {
                log_warning('sendsms', $e->getMessage());
                // throw new ApiException(ApiException::Remind, $e->getMessage());
            }
        }

        return $user;
    }
    
    public function SMSLoginAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');

        $code = smscode();

        try {
            send_sms($mobile, 0, 'login_phone', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }
}