<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\UcuserOauth;
use App\Model\UcuserInfo;

class OauthController extends Controller {

    use LoginAction, RegisterAction;
/*
    public function SMSBindAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');

        $code = smscode();

        try {
            send_sms($mobile, 0, 'oauth_login_bind', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function getRegisterUser() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');
        $openid = $this->parameter->tough('openid');
        $unionid = $this->parameter->get('unionid');
        $type = $this->parameter->tough('type');
        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');

        $types = ['weixin' => '微信', 'qq' => 'QQ', 'weibo' => '微博'];

        if(!isset($types[$type])) {
            throw new ApiException(ApiException::Error, "未知的平台登录类型, type={$type}");
        }

        // ----------- 验证码
        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        // ----------- 获取用户
        $openid = md5($type .'_'. $openid);
        $unionid = $unionid ? md5($type .'_'. $unionid) : '';

        $user_oauth = null;

        if($unionid) {
            $user_oauth = UcuserOauth::from_cache_unionid($unionid);
        }

        if(!$user_oauth) {
            $user_oauth = UcuserOauth::from_cache_openid($openid);
        }

        $mobile_user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        // ------------ oauth存在 --> 绑定mobile
        if($user_oauth) {
            $user = Ucuser::from_cache($user_oauth->ucid);

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
            $count = UcuserOauth::where('type', $type)->where('ucid', $mobile_user->ucid)->count();
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
            
            $mobile_user = new Ucuser;
            $mobile_user->uid = $username;
            $mobile_user->email = $username . "@anfan.com";
            $mobile_user->mobile = $mobile;
            $mobile_user->nickname = $nickname;
            $mobile_user->avatar = $avatar;
            $mobile_user->password = $password;
            $mobile_user->regip = $this->request->ip();
            $mobile_user->rid = $this->parameter->tough('_rid');
            $mobile_user->pid = $this->parameter->tough('_appid');
            $mobile_user->regdate = date('Ymd');
            $mobile_user->date = date('Ymd');
            $mobile_user->save();

            user_log($mobile_user, $this->procedure, 'register', '【注册】通过%s注册，绑定手机{%s}，密码[%s]，openid:<%s>', $types[$type], $mobile, $mobile_user->password, $openid);

            try {
                send_sms($mobile, 0, 'oauth_register', ['#type#' => $types[$type], '#username#' => $username, '#password#' => $password]);
            } catch (\App\Exceptions\Exception $e) {
                // throw new ApiException(ApiException::Remind, $e->getMessage());
            }
        }
        
        // ------------ 绑定平台账号
        $user_oauth = new UcuserOauth;
        $user_oauth->ucid = $mobile_user->ucid;
        $user_oauth->type = $type;
        $user_oauth->openid = $openid;
        $user_oauth->uuid = $uuid;
        $user_oauth->saveAndCache();

        return $mobile_user;
    }
*/

    public function getRegisterUser() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid');
        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');

        $ctype = config("common.oauth.{$type}", false);
        if(!$ctype) {
            throw new ApiException(ApiException::Error, '未知的第三方登陆类型，type='.$type);
        }

        $openid = md5($type .'_'. $openid);
        $unionid = $unionid ? md5($type .'_'. $unionid) : '';

        $user_oauth = null;

        if($unionid) {
            $user_oauth = UcuserOauth::from_cache_unionid($unionid);
        }

        if(!$user_oauth) {
            $user_oauth = UcuserOauth::from_cache_openid($openid);
        }

        if($user_oauth) {
            $user = Ucuser::from_cache($user_oauth->ucid);
            return $user;
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = '';
        $user->nickname = $nickname ?: $username;
        $user->setPassword($password);
        $user->regip = $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->save();

        $user_oauth = new UcuserOauth;
        $user_oauth->ucid = $user->ucid;
        $user_oauth->type = $type;
        $user_oauth->openid = $openid;
        $user_oauth->unionid = $unionid;
        $user_oauth->saveAndCache();

        $user_info = UcuserInfo::from_cache($user->ucid);
        if(!$user_info) {
            $user_info = new UcuserInfo;
            $user_info->ucid = $user->ucid;
            $user_info->avatar = $avatar;
            $user_info->saveAndCache();
        }

        user_log($user, $this->procedure, 'register', '【注册】通过%s注册，密码[%s]', $ctype['text'], $user->password);
        
        return $user;
    }

    public function getLoginUser() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid');

        $openid = md5($type .'_'. $openid);
        $unionid = $unionid ? md5($type .'_'. $unionid) : '';

        $user_oauth = null;

        if($unionid) {
            $user_oauth = UcuserOauth::from_cache_unionid($unionid);
        }

        if(!$user_oauth) {
            $user_oauth = UcuserOauth::from_cache_openid($openid);
        }
        
        if(!$user_oauth) {
            throw new ApiException(ApiException::OauthNotRegister, "尚未注册");
        }

        $user = Ucuser::from_cache($user_oauth->ucid);
        return $user;
    }
}