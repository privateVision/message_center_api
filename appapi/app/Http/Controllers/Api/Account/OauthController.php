<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\User;
use App\Model\UserOauth;

class OauthController extends Controller {

    use LoginAction, RegisterAction;

    public function SMSBindAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');

        $code = smscode();

        try {
            send_sms($mobile, env('APP_ID'), 'oauth_login_bind', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function getRegisterUser(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');
        $openid = $parameter->tough('openid');
        $type = $parameter->tough('type');
        $nickname = $parameter->get('nickname');
        $avatar = $parameter->get('avatar');

        $types = ['weixin' => '微信', 'qq' => 'QQ', 'weibo' => '微博'];

        if(!isset($types[$type])) {
            throw new ApiException(ApiException::Error, "未知的平台登录类型, type={$type}");
        }

        // ----------- 验证码
        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $uuid = md5($type . $openid);
        $user_oauth = UserOauth::where('uuid', $uuid)->first();
        $mobile_user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();

        // ------------ oauth存在 --> 绑定mobile
        if($user_oauth) {
            $user = User::from_cache($user_oauth->ucid);

            if($user->mobile && $user->mobile != $mobile) {
                throw new ApiException(ApiException::AlreadyBindMobile, '账号已经绑定了手机号码');
            }

            if($user->is_freeze) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            if($mobile_user && $mobile_user->ucid != $user->ucid) {
                throw new ApiException(ApiException::MobileBindOther, '手机号码已经绑定了其它账号');
            }

            $user->mobile = $mobile;

            if(!$user->avatar && $avatar) {
                $user->avatar = $avatar;
            }

            if(!$user->nickname && $nickname) {
                $user->nickname = $nickname;
            }
            
            $user->delaySave();

            user_log($mobile_user, $this->procedure, 'bind_oauth', '【绑定手机】通过平台注册时绑定手机:{%s}', $mobile);

            return $user;
        }

        // ------------ mobile存在 --> 绑定oauth
        if($mobile_user) {
            // 验证是否绑定过平台账号
            $count = UserOauth::where('type', $type)->where('ucid', $mobile_user->ucid)->count();
            if($count > 0) {
                throw new ApiException(ApiException::AlreadyBindOauth, '账号已经绑定了其它'.$types[$type]);
            }

            if($mobile_user->is_freeze) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            // 修改头像，昵称
            if(!$mobile_user->avatar && $avatar) {
                $mobile_user->avatar = $avatar;
            }

            if(!$mobile_user->nickname && $nickname) {
                $mobile_user->nickname = $nickname;
            }

            $mobile_user->delaySave();

            user_log($mobile_user, $this->procedure, 'bind_oauth', '【绑定%s】openid:<%s>', $types[$type], $types[$type], $openid);
        }

        // ------------ oauth不存在、mobile不存在 --> 注册
        if(!$mobile_user) {
            $username = username();
            $password = rand(100000, 999999);
            
            $mobile_user = new User;
            $mobile_user->uid = $username;
            $mobile_user->email = $username . "@anfan.com";
            $mobile_user->mobile = $mobile;
            $mobile_user->nickname = $nickname;
            $mobile_user->avatar = $avatar;
            $mobile_user->password = $password;
            $mobile_user->regip = $request->ip();
            $mobile_user->rid = $parameter->tough('_rid');
            $mobile_user->pid = $parameter->tough('_appid');
            $mobile_user->regdate = date('Ymd');
            $mobile_user->date = date('Ymd');
            $mobile_user->save();

            user_log($mobile_user, $this->procedure, 'register', '【注册】通过%s注册，绑定手机{%s}，密码[%s]，openid:<%s>', $types[$type], $mobile, $mobile_user->password, $openid);

            try {
                send_sms($mobile, env('APP_ID'), 'oauth_register', ['#type#' => $types[$type], '#username#' => $username, '#password#' => $password]);
            } catch (\App\Exceptions\Exception $e) {
                // throw new ApiException(ApiException::Remind, $e->getMessage());
            }
        }
        
        // ------------ 绑定平台账号
        $user_oauth = new UserOauth;
        $user_oauth->ucid = $mobile_user->ucid;
        $user_oauth->type = $type;
        $user_oauth->openid = $openid;
        $user_oauth->uuid = $uuid;

        return $mobile_user;
    }

    public function getLoginUser(Request $request, Parameter $parameter) {
        $openid = $parameter->tough('openid');
        $type = $parameter->tough('type');

        $user_oauth = UserOauth::where('type', $type)->where('openid', $openid)->first();
        if(!$user_oauth) {
            throw new ApiException(ApiException::OauthNotRegister, '用户尚未注册');
        }

        $user = User::from_cache($user_oauth->ucid);

        return $user;
    }
}