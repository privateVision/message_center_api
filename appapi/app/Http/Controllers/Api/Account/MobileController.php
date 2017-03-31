<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;

class MobileController extends Controller {

    use LoginAction;

    public function getLoginUser() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        // 登录
        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            return $user;
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);

        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = $mobile;
        $user->nickname = $mobile;
        $user->password = $password;
        $user->regip = $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->save();

        user_log($user, $this->procedure, 'register', '【手机号码登录】检测到尚未注册，手机号码{%s}，密码[%s]', $mobile, $user->password);

        // 将密码发给用户，通过队列异步发送
        try {
            send_sms($mobile, 0, 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // throw new ApiException(ApiException::Remind, $e->getMessage());
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