<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Event;
use App\Model\Ucusers;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\YunpianSms;

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

        $ucuser = null;
        $ucusers = Ucusers::where('uid', $username)->orWhere('mobile', $username)->get();
        foreach($ucusers as $v) {
            if($v->ucenter_members->checkPassword($password)) {
                $ucuser = $v;
            }
        }

        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, "登陆失败，用户名或者密码不正确");
        }

        return Event::onLogin($ucuser, $this->session);
    }

    public function RegisterAction(Request $request, Parameter $parameter){
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

    public function UsernameAction(Request $request, Parameter $parameter) {
        $username = null;

        $chars = 'abcdefghjkmnpqrstuvwxy';
        do {
            $username = $chars[rand(0, 21)] . rand(10000, 99999999);
            $count = Ucusers::where('uid', $username)->count();
        } while($count > 0);

        return ['username' => $username];
    }

    public function PhoneLoginAction(Request $request, Parameter $parameter) {
        $yunpiansms = YunpianSms::where('text', $this->session->access_token)->first();
        if(!$yunpiansms) {
            return null;
        }

        $mobile = $yunpiansms->mobile;

        // 登陆
        $ucuser = Ucusers::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($ucuser) {
            return Event::onLogin($ucuser, $this->session);
        }

        // 注册
        $password = rand(100000, 999999);

        $UcenterMember = new UcenterMembers;
        $UcenterMember->password = $password;
        $UcenterMember->email = $mobile . "@anfan.com";;
        $UcenterMember->regip = $request->ip();
        $UcenterMember->username = $mobile;
        $UcenterMember->regdate = time();
        $UcenterMember->save();

        $ucuser = $UcenterMember->ucusers()->create([
            'uid' => $mobile,
            'mobile' => $mobile,
            'uuid' => $this->session->access_token,
            'rid' => $this->session->rid,
            'pid' => $this->session->pid,
        ]);

        // 将密码发给用户，通过队列异步发送
        kafkaProducer('sendsms', ['mobile' => $mobile, 'content' => "恭喜您注册成功，你的用户名:{$mobile}，密码是:{$password}"]);

        return Event::onRegister($ucuser, $this->session);
    }
}