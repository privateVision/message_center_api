<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Event;
use App\Model\Ucusers;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\YunpianCallback;

class AccountController extends BaseController {

    protected $session = null;

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        $token = $parameter->tough('token');

        /* todo: 注释掉，这里兼容旧的代码
        $session = Session::where('access_token', $access_token)->first();
        if(!$session || !$session->ucid) {
            throw new ApiException(ApiException::Remind, '登陆失败，请重新登陆');
        }

        if($session->expired_ts < time()) {
            throw new ApiException(ApiException::Remind, '登陆失败，会话已经过期，请重新登陆');
        }

        $ucuser = Ucusers::find($session->ucid);
        */

        $ucuser = Ucusers::where('uuid', $token)->first();
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '用户不存在，请重新登陆');
        }

        if($ucuser->isFreeze()) {
            throw new ApiException(ApiException::AccountFreeze, '帐号已被冻结，无法登陆');
        }

        return Event::onLogin($ucuser, $this->session);
    }

    public function LoginAction(Request $request, Parameter $parameter) {
        $username = $parameter->tough('username');
        $password = $parameter->tough('password');

        $ucuser = null;

        $ucusers = Ucusers::where('uid', $username)->orWhere('mobile', $username)->get();
        foreach($ucusers as $v) {
            if($v->isFreeze()) {
                if($v->checkServicePassword($password)) {
                    $ucuser = $v;
                    break;
                } else {
                    throw new ApiException(ApiException::AccountFreeze, '帐号已被冻结，无法登陆');
                }
            } elseif($v->ucenter_members && $v->ucenter_members->checkPassword($password)) {
                $ucuser = $v;
                break;
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

        if(!check_name($username, 24)){
            throw new ApiException(ApiException::Remind, "用户名格式不正确，请填写正确的格式");
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
            if($count ==0 ) return ['username' => $username];
        } while(true);

    }

    public function LoginPhoneAction(Request $request, Parameter $parameter) {
        $yunpian_callback = YunpianCallback::where('text', $this->session->access_token)->first();
        if(!$yunpian_callback) {
            return null;
        }

        $mobile = $yunpian_callback->mobile;

        // 登陆
        $ucuser = Ucusers::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($ucuser) {
            if($ucuser->isFreeze()) {
                throw new ApiException(ApiException::AccountFreeze, '帐号已被冻结，无法登陆');
            }

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
        send_sms($mobile, trans('messages.phone_register', ['username' => $mobile, 'password' => $password]));

        return Event::onRegister($ucuser, $this->session);
    }

    /*
     * 更改密码
     * */

    public function changePassAction(Request $request ,Parameter $parameter){
        $oldPass  = $parameter->tough('oldPass');
        $newPass  = $parameter->tough('newPass');
        $userName = $parameter->tough("userName");

        if(!check_name($userName)) {
            throw new ApiException(ApiException::Remind, "用户名格式不正确，请不要使用特殊字符");
        }
        $user = UcenterMembers::where("username",$userName)->get();

        foreach($user as $v) {
            //满足当前的对象未被冻结
            if($v->checkPassword($oldPass)   &&  $v->ucusers_extend->isfreeze == 0 ) {
                $v->setPasswordAttribute($newPass);
                if($v->save()){
                    return Event::onLogout($v,$this->session); //修改密码退出
                }else{
                    throw new ApiException(ApiException::Remind, "修改密码失败！");
                }
            }
        }
        
    }


}