<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\User;

class MobileController extends Controller {

    use LoginAction;

    public function getLoginUser(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        // 登录
        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            return $user;
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);

        $user = new User;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = $mobile;
        $user->nickname = $mobile;
        $user->password = $password;
        $user->regip = $request->ip();
        $user->rid = $parameter->tough('_rid');
        $user->pid = $parameter->tough('_appid');
        $user->regdate = date('Ymd');
        $user->date = date('Ymd');
        $user->save();

        user_log($user, $this->procedure, 'register', '【手机号码登录】检测到尚未注册，手机号码{%s}，密码[%s]', $mobile, $user->password);

        // 将密码发给用户，通过队列异步发送
        try {
            send_sms($mobile, env('APP_ID'), 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return $user;
    }
    
    public function SMSLoginAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');

        $code = smscode();

        try {
            send_sms($mobile, env('APP_ID'), 'login_phone', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }
}