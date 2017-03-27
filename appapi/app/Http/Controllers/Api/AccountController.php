<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Event;
use App\Redis;
use App\Model\Session;
use App\Model\User;
use App\Model\YunpianCallback;
use App\Model\UserOauth;

class AccountController extends Controller {

    public function LoginGuestAction(Request $request, Parameter $parameter) {
        $uuid = $parameter->tough('_device_id');

        $rediskey = sprintf('guest_%s', $uuid);
        $ucid = Redis::get($rediskey);
        if($ucid) {
            $user = User::from_cache($ucid);
        }

        if(!isset($user)) {
            $username = username();
            $password = rand(100000, 999999);

            $user = new User;
            $user->password = $password;
            $user->email = $username . "@anfan.com";;
            $user->regip = $request->ip();
            $user->uid = $username;
            $user->nickname = $username;
            $user->rid = $parameter->tough('_rid');
            $user->uuid = '';
            $user->pid = $parameter->tough('_appid');
            $user->save();

            Redis::set($rediskey, $user->ucid);

            user_log($user, $this->procedure, 'register', '【游客注册】用户名(%s), 密码[%s]', $username, $user->password);
            
            return Event::onRegisterAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        }

        return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function OauthSMSBindAction(Request $request, Parameter $parameter) {
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

    public function OauthRegisterAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');
        $openid = $parameter->tough('openid');
        $type = $parameter->tough('type');
        $nickname = $parameter->get('nickname');
        $avatar = $parameter->get('avatar');

        $types = ['weixin' => '微信', 'qq' => 'QQ', 'weibo' => '微博'];

        if(!isset($types[$type])) {
            throw new ApiException(ApiException::Error, "未知的登录类型, type={$type}");
        }

        // ----------- 验证验证码
        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $uuid = md5($type . $openid);
        $user_oauth = UserOauth::where('uuid', $uuid)->first();
        $mobile_user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();

        // ------------ openid存在，修改昵称、头像并登录
        if($user_oauth) {
            $user = User::find($user_oauth->ucid);

            if($user->mobile && $user->mobile != $mobile) {
                throw new ApiException(ApiException::AlreadyBindMobile, '账号已经绑定了手机号码');
            }

            if($user->is_freeze) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            if($mobile_user && $mobile_user->ucid != $user->ucid) {
                throw new ApiException(ApiException::MobileBindOther, '手机号码已经绑定了其它账号');
            }
            
            // 绑定手机号码
            $user->mobile = $mobile;
            if(!$user->avatar && $avatar) {
                $user->avatar = $avatar;
            }

            if(!$user->nickname && $nickname) {
                $user->nickname = $nickname;
            }
            
            $user->delaySave();

            user_log($mobile_user, $this->procedure, 'register', '【第三方平台账号注册】平台账号曾经注册，绑定手机{%s}', $mobile);

            return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        }

        // ------------ mobile存在，修改昵称、头像
        if($mobile_user) {
            // 验证是否绑定过平台账号
            $count = UserOauth::where('type', $type)->where('ucid', $mobile_user->ucid)->count();
            if($count > 0) {
                throw new ApiException(ApiException::AlreadyBindOauth, '账号已经绑定了'.$types[$type]);
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

            user_log($mobile_user, $this->procedure, 'register', '【第三方平台账号注册】手机号码曾经注册，绑定平台账号：%s', $types[$type]);
        }

        // ------------ 都不存在，注册
        $username = username();
        
        if(!$mobile_user) {
            $password = rand(100000, 999999);

            $mobile_user = new User;
            $mobile_user->uid = $username;
            $mobile_user->mobile = $mobile;
            $mobile_user->rid = $parameter->tough('_rid');
            $mobile_user->uuid = '';
            $mobile_user->pid = $parameter->tough('_appid');
            $mobile_user->password = $password;
            $mobile_user->email = $mobile . "@anfan.com";
            $mobile_user->regip = $request->ip();
            $mobile_user->avatar = $avatar;
            $mobile_user->nickname = $nickname;
            $mobile_user->save();

            user_log($mobile_user, $this->procedure, 'register', '【第三方平台账号注册】通过%s注册，绑定手机{%s}，密码[%s]', $types[$type], $mobile, $mobile_user->password);

            try {
                send_sms($mobile, env('APP_ID'), 'oauth_register', ['#type#' => $types[$type], '#username#' => $username, '#password#' => $password]);
            } catch (\App\Exceptions\Exception $e) {
                // 注册成功就OK了，短信发送失败没关系，可找回密码
                // throw new ApiException(ApiException::Remind, $e->getMessage());
            }

            $is_register = true;
        }
        
        // ------------ 绑定平台账号
        $user_oauth = new UserOauth;
        $user_oauth->type = $type;
        $user_oauth->openid = $openid;
        $user_oauth->uuid = $uuid;
        $mobile_user->user_oauth()->save($user_oauth);

        if(isset($is_register)) {
            return Event::onRegisterAfter($mobile_user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        } else {
            return Event::onLoginAfter($mobile_user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        }
    }

    public function OauthLoginAction(Request $request, Parameter $parameter) {
        $openid = $parameter->tough('openid');
        $type = $parameter->tough('type');

        $user_oauth = UserOauth::where('type', $type)->where('openid', $openid)->first();
        if(!$user_oauth) {
            throw new ApiException(ApiException::OauthNotRegister, '用户尚未注册');
        }

        $user = User::find($user_oauth->ucid);

        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
        }
        
        return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        $token = $parameter->tough('_token');

        $session = Session::where('token', $token)->first();
        if(!$session) {
            throw new ApiException(ApiException::Remind, '会话已结束，请重新登录');
        }

        if(!$session->ucid) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        $user = User::find($session->ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
        }
        
        return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function LoginAction(Request $request, Parameter $parameter) {
        $username = $parameter->tough('username', 'username');
        $password = $parameter->tough('password');

        $user = User::where('uid', $username)->orWhere('mobile', $username)->first();

        if(!$user || !$user->checkPassword($username)) {
            throw new ApiException(ApiException::Remind, "登录失败，用户名或者密码不正确");
        }

        if($user->is_freeze) {
            throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
        }

        return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function RegisterAction(Request $request, Parameter $parameter){
        $username = $parameter->tough('username', 'username');
        $password = $parameter->tough('password');

        $isRegister  = User::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登录");
        }

        $user = new User;
        $user->password = $password;
        $user->email = $username . "@anfan.com";;
        $user->regip = $request->ip();
        $user->uid = $username;
        $user->nickname = $username;
        $user->rid = $parameter->tough('_rid');
        $user->uuid = '';
        $user->pid = $parameter->tough('_appid');
        $user->save();

        user_log($user, $this->procedure, 'register', '【用户名注册】通过用户名注册，用户名(%s), 密码[%s]', $username, $user->password);

        return Event::onRegisterAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function UsernameAction(Request $request, Parameter $parameter) {
        $username = null;

        $chars = 'abcdefghjkmnpqrstuvwxy';
        do {
            $username = $chars[rand(0, 21)] . rand(10000, 99999999);

            $count = User::where('uid', $username)->count();
            if($count == 0) {
                return ['username' => $username];
            }
        } while(true);
    }

    public function LoginOnekeyAction(Request $request, Parameter $parameter) {
        $sms_token = $parameter->tough('sms_token');

        $yunpian_callback = YunpianCallback::where('text', $sms_token)->first();

        if(!$yunpian_callback) {
            throw new ApiException(ApiException::MobileNotRegister, '服务器等待收到短信...');
        }

        $mobile = $yunpian_callback->mobile;

        // 登录
        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->is_freeze) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);

        $user = new User;
        $user->password = $password;
        $user->uid = $username;
        $user->email = $mobile . "@anfan.com";;
        $user->regip = $request->ip();
        $user->mobile = $mobile;
        $user->nickname = $mobile;
        $user->rid = $parameter->tough('_rid');
        $user->uuid = '';
        $user->pid = $parameter->tough('_appid');
        $user->save();

        user_log($user, $this->procedure, 'register', '【手机号码一键登录】检测到尚未注册，手机号码{%s}, 密码[%s]', $mobile, $user->password);

        // 将密码发给用户，通过队列异步发送
        try {
            send_sms($mobile, env('APP_ID'), 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // 注册成功就OK了，短信发送失败没关系，可找回密码
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return Event::onRegisterAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
    }

    public function SMSOnekeyTokenAction(Request $request, Parameter $parameter) {
        $config = config('common.apps.'.env('APP_ID'));
        if(!$config) {
            throw new ApiException(ApiException::Error, '短信接口未配置');
        }

        return ['sms_token' => uuid(), 'send_to' => $config->sms_receiver];
    }

    public function SMSLoginPhoneAction(Request $request, Parameter $parameter) {
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

    public function LoginPhoneAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        // 登录
        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->is_freeze) {
                throw new ApiException(ApiException::AccountFreeze, '账号已被冻结，无法登录');
            }

            return Event::onLoginAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);

        $user = new User;
        $user->password = $password;
        $user->uid = $username;
        $user->email = $mobile . "@anfan.com";;
        $user->regip = $request->ip();
        $user->mobile = $mobile;
        $user->nickname = $mobile;
        $user->rid = $parameter->tough('_rid');
        $user->uuid = '';
        $user->pid = $parameter->tough('_appid');
        $user->save();

        user_log($user, $this->procedure, 'register', '【手机号码登录】检测到尚未注册，手机号码{%s}，密码[%s]', $mobile, $user->password);

        // 将密码发给用户，通过队列异步发送
        try {
            send_sms($mobile, env('APP_ID'), 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // 注册成功就OK了，短信发送失败没关系，可找回密码
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return Event::onRegisterAfter($user, $parameter->tough('_appid'), $parameter->tough('_rid'));
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
        $password = $parameter->tough('password');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if(!$user) {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $old_password = $user->password;
        $user = Event::onResetPassword($user, $password);
        user_log($user, $this->procedure, 'reset_password', '【重置用户密码】通过手机验证码重置，手机号码{%s}，旧密码[%s]，新密码[%s]', $mobile, $old_password, $user->password);

        return ['result' => true];
    }
}