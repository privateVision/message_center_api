<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\User;

class UserController extends Controller {

    use LoginAction;

    public function getLoginUser(Request $request, Parameter $parameter) {
        $username = $parameter->tough('username');
        $password = $parameter->tough('password');

        $user = User::where('uid', $username)->orWhere('mobile', $username)->first();

        if(!$user || !$user->checkPassword($password)) {
            throw new ApiException(ApiException::Remind, "登录失败，用户名或者密码不正确");
        }

        return $user;
    }
    
    public function SMSResetPasswordAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');

        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if(!$user) {
            throw new ApiException(ApiException::Remind, '手机号码尚未注册');
        }

        $code = smscode();

        try {
            send_sms($mobile, env('APP_ID'), 'reset_password', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function ResetPasswordAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');
        $new_password = $parameter->tough('password');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if(!$user) {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $old_password = $user->password;

        $user->password = $new_password;
        $user->save();
        
        user_log($user, $this->procedure, 'reset_password', '【重置密码】通过手机验证码重置，手机号码{%s}，旧密码[%s]，新密码[%s]', $mobile, $old_password, $user->password);

        return ['result' => true];
    }
    
    public function RegisterAction(Request $request, Parameter $parameter){
        $username = $parameter->tough('username', 'username');
        $password = $parameter->tough('password');

        $isRegister  = User::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登录");
        }

        $user = new User;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = $username;
        $user->password = $password;
        $user->regip = $request->ip();
        $user->rid = $parameter->tough('_rid');
        $user->pid = $parameter->tough('_appid');
        $user->regdate = date('Ymd');
        $user->date = date('Ymd');
        $user->save();

        user_log($user, $this->procedure, 'register', '【注册】通过“用户名”注册，用户名(%s), 密码[%s]', $username, $user->password);
    }

    public function UsernameAction(Request $request, Parameter $parameter) {
        return ['username' => username()];
    }
}