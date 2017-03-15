<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Event;
use App\Model\Session;
use App\Model\Ucusers;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\YunpianCallback;
use App\Model\SMSRecord;


class AccountController extends Controller {

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        $token = $parameter->tough('token');

        $session = Session::where('token', $token)->first();
        if(!$session) {
            throw new ApiException(ApiException::Remind, '会话已结束，请重新登录');
        }

        if(!$session->ucid) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        $ucuser = Ucusers::find($session->ucid);
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        if($ucuser->isFreeze()) {
            throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
        }

        $ucuser->getConnection()->beginTransaction();
        $response = Event::onLoginAfter($ucuser, $parameter->tough('_appid'), $parameter->tough('_rid'));
        $ucuser->getConnection()->commit();

        return $response;
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
                    throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
                }
            } elseif($v->checkPassword($password)) {
                $ucuser = $v;
                break;
            }
        }

        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, "登录失败，用户名或者密码不正确");
        }

        $ucuser->getConnection()->beginTransaction();
        $response = Event::onLoginAfter($ucuser, $parameter->tough('_appid'), $parameter->tough('_rid'));
        $ucuser->getConnection()->commit();

        return $response;
    }

    public function RegisterAction(Request $request, Parameter $parameter){
        $username = $parameter->tough('username');
        $password = $parameter->tough('password');

        if(!check_name($username, 24)){
            throw new ApiException(ApiException::Remind, "用户名格式不正确，请填写正确的格式");
        }

        $isRegister  = Ucusers::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登录");
        }

        $UcenterMember = new UcenterMembers;
        $UcenterMember->password = $password;
        $UcenterMember->email = $username . "@anfan.com";;
        $UcenterMember->regip = $request->ip();
        $UcenterMember->username = $username;
        $UcenterMember->regdate = time();
        $UcenterMember->save();

        $ucuser = new Ucusers;
        $ucuser->getConnection()->beginTransaction();

        $ucuser->ucid = $UcenterMember->uid;
        $ucuser->uid = $username;
        $ucuser->rid = $parameter->tough('_rid');
        $ucuser->uuid = '';
        $ucuser->pid = $parameter->tough('_appid');
        $ucuser->save();

        $response = Event::onRegisterAfter($ucuser, $parameter->tough('_appid'), $parameter->tough('_rid'));

        $ucuser->getConnection()->commit();

        return $response;
    }

    public function UsernameAction(Request $request, Parameter $parameter) {
        $username = null;

        $chars = 'abcdefghjkmnpqrstuvwxy';
        do {
            $username = $chars[rand(0, 21)] . rand(10000, 99999999);

            $count = Ucusers::where('uid', $username)->count();
            if($count == 0) {
                return ['username' => $username];
            }
        } while(true);

    }

    public function LoginPhoneAction(Request $request, Parameter $parameter) {
        $sms_token = $parameter->tough('sms_token');

        $yunpian_callback = YunpianCallback::where('text', $sms_token)->first();

        if(!$yunpian_callback) {
            return null;
        }

        $mobile = $yunpian_callback->mobile;

        // 登陆
        $ucuser = Ucusers::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($ucuser) {
            if($ucuser->isFreeze()) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            return Event::onLoginAfter($ucuser, $parameter->tough('_appid'), $parameter->tough('_rid'));
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

        $ucuser = new Ucusers;
        $ucuser->getConnection()->beginTransaction();

        $ucuser->ucid = $UcenterMember->uid;
        $ucuser->uid = $mobile;
        $ucuser->mobile = $mobile;
        $ucuser->rid = $parameter->tough('_rid');
        $ucuser->uuid = '';
        $ucuser->pid = $parameter->tough('_appid');
        $ucuser->save();

        $response = Event::onRegisterAfter($ucuser, $parameter->tough('_appid'), $parameter->tough('_rid'));

        $ucuser->getConnection()->commit();

        // 将密码发给用户，通过队列异步发送
        try {
            $content = send_sms($mobile, env('APP_ID'), 'onekey_mobile_register', ['#username#' => $mobile, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // 注册成功就OK了，短信发送失败没关系，可找回密码
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return $response;
    }

    public function SMSTokenAction(Request $request, Parameter $parameter) {
        $config = config('common.apps.'.env('APP_ID'));
        if(!$config) {
            throw new ApiException(ApiException::Error, '短信接口未配置');
        }

        return ['sms_token' => uuid(), 'send_to' => $config->sms_receiver];
    }

    public function SMSResetPasswordAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile');

        $ucuser = Ucusers::where('mobile', $mobile)->first();
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $code = rand(100000, 999999);

        try {
            $content = send_sms($mobile, env('APP_ID'), 'reset_password', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return ['code' => md5($code . $this->procedure->appkey())];
    }

    public function ResetPasswordAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile');
        $code = $parameter->tough('code');
        $password = $parameter->tough('password');

        $SMSRecord = SMSRecord::verifyCode($mobile, $code);

        if(!$SMSRecord) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $ucuser = Ucusers::where('mobile', $mobile)->first();
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $ucuser->setNewPassword($password);

        return ['result' => true];
    }
}