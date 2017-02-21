<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Event;
use App\Model\Ucusers;
use App\Model\Gamebbs56\UcenterMembers;

class AccountController extends BaseController {

    protected $session = null;

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        $access_token = $parameter->tough('token');

        $session = Session::where('access_token', $access_token)->first();
        if(!$session || !$session->ucid) {
            throw new ApiException(ApiException::Remind, '登陆失败，请重新登陆');
        }

        if($session->expired_ts < time()) {
            throw new ApiException(ApiException::Remind, '登陆失败，会话已经过期，请重新登陆');
        }

        $ucuser = Ucusers::find($session->ucid);
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '用户不存在，请重新登陆');
        }

        return Event::onLogin($ucuser, $this->session);
    }

    public function LoginAction(Request $request, Parameter $parameter) {
        $username = $parameter->tough('username');
        $password = $parameter->tough('password');

        $UcenterMembers = UcenterMembers::where('username', $username)->get();
        $UcenterMember = null;
        foreach($UcenterMembers as $v) {
            if($v->checkPassword($password)) {
                $UcenterMember = $v;
                break;
            }
        }

        if(!$UcenterMember) {
            throw new ApiException(ApiException::Remind, "登陆失败，用户名或者密码不正确");
        }

        return Event::onLogin($UcenterMember->ucusers, $this->session);
    }

    public function userRegisterAction(Request $request, Parameter $parameter){
        $username   = $parameter->tough('username') ;
        $password   = $parameter->tough('password') ;

        if(!preg_match('/^[\w\_\-\.\@\:]+$/', $username)) {
            throw new ApiException(ApiException::Remind, "用户名格式不正确，请不要使用特殊字符");
        }

        if(preg_match('/^[\d]+$/', $username)) {
            throw new ApiException(ApiException::Remind, "用户名至少包含有一个字母");
        }

        if(strlen($username) > 24) {
            throw new ApiException(ApiException::Remind, "用户名最多可以有24个字符");
        }

        $isRegister  = Ucusers::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登陆");
        }

        $UcenterMember = new UcenterMembers;
        $UcenterMember->password = $password;
        $UcenterMember->email = $username . "@anfan.com";;
        $UcenterMember->regip = $request->ip();
        $UcenterMember->username = $username;
        $UcenterMember->regdate = time();
        $UcenterMember->save();

        $ucuser = $UcenterMember->ucusers()->create([
            'uid' => $username,
            'uuid' => $this->session->access_token,
            'rid' => $this->session->rid,
            'pid' => $this->session->pid,
        ]);

        return Event::onRegister($ucuser, $this->session);
    }
}