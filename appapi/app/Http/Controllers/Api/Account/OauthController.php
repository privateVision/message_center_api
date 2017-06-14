<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;

use App\Model\Ucuser;
use App\Model\UcuserOauth;
use App\Model\UcuserInfo;
use App\Model\UcusersUUID;

class OauthController extends Controller {

    use LoginAction, RegisterAction;

    const Type = 3;
<<<<<<< HEAD

=======
    
>>>>>>> dev
    public function getRegisterUser() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid', '');
        
        if($type == 'weixin' && $unionid == '') throw new ApiException(ApiException::Error, trans('messages.unionid_empty'));

        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');

        $ctype = config("common.oauth.{$type}", false);
        if(!$ctype) {
            throw new ApiException(ApiException::Error, trans('messages.3th_unknow', ['type' => $type]));
        }

        $openid = "{$openid}@{$type}";
        $unionid = $unionid ? "{$unionid}@{$type}" : '';

        $user_oauth = null;

        if($unionid) {
            $user_oauth = UcuserOauth::from_cache_unionid($unionid);
        }

        if(!$user_oauth) {
            $user_oauth = UcuserOauth::from_cache_openid($openid);
        }

        if($user_oauth) {
            $user = Ucuser::from_cache($user_oauth->ucid);
            if($user) return $user;
        }

        // 注册
        $username = username();
        $password = rand(100000, 999999);
<<<<<<< HEAD
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = '';
        $user->nickname = $nickname ?: $username;
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = getClientIp();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->imei = $this->parameter->get('_imei', '');
        $user->device_id = $this->parameter->get('_device_id', '');
        $user->save();
=======

        //平台注册账号
        $user = self::baseRegisterUser([
            'uid' => $username,
            'nickname' => $nickname ?: $username,
            'password' => $password
        ]);
>>>>>>> dev

        $user_oauth = new UcuserOauth;
        $user_oauth->ucid = $user->ucid;
        $user_oauth->type = $type;
        $user_oauth->openid = $openid;
        $user_oauth->unionid = $unionid;
        $user_oauth->saveAndCache();

        $user_info = new UcuserInfo;
        $user_info->ucid = $user->ucid;
        $user_info->avatar = $avatar ? $avatar:env('default_avatar');
        $user_info->saveAndCache();

        user_log($user, $this->procedure, 'register', '【注册】通过%s注册，密码[%s]', $ctype['text'], $user->password);
        
        return $user;
    }

    public function getLoginUser() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid', "");
        $forced = $this->parameter->get('forced');
        
        if($type == 'weixin' && $unionid == '') throw new ApiException(ApiException::Error, trans('messages.unionid_empty'));

        $openid = "{$openid}@{$type}";;
        $unionid = $unionid ? "{$unionid}@{$type}" : '';

        $user_oauth = null;

        if($unionid) {
            $user_oauth = UcuserOauth::from_cache_unionid($unionid);
        }

        if(!$user_oauth) {
            $user_oauth = UcuserOauth::from_cache_openid($openid);
        }
        
        if(!$user_oauth) {
            if($forced == '1') {
                // 注册
                $ctype = config("common.oauth.{$type}", false);
                if(!$ctype) {
                    throw new ApiException(ApiException::Error, trans('messages.3th_unknow', ['type' => $type]));
                }

                $nickname = $this->parameter->get('nickname');
                $avatar = $this->parameter->get('avatar');

                $username = username();
                $password = rand(100000, 999999);
                
//                $user = new Ucuser;
//                $user->uid = $username;
//                $user->email = $username . "@anfan.com";
//                $user->mobile = '';
//                $user->nickname = $nickname ?: $username;
//                $user->setPassword($password);
//                $user->regtype = static::Type;
//                $user->regip = getClientIp();
//                $user->rid = $this->parameter->tough('_rid');
//                $user->pid = $this->procedure->pid;
//                $user->regdate = time();
//                $user->save();
                $udt = array(
                    'uid'=>$username,
                    'nickname'=>$nickname ?: $username,
                    'password'=>$password
                );
                //平台注册账号
                $user = self::baseRegisterUser($udt);
                
                $user_oauth = new UcuserOauth;
                $user_oauth->ucid = $user->ucid;
                $user_oauth->type = $type;
                $user_oauth->openid = $openid;
                $user_oauth->unionid = $unionid;
                $user_oauth->saveAndCache();

                $user_info = new UcuserInfo;
                $user_info->ucid = $user->ucid;
                $user_info->avatar = $avatar ? $avatar : env('default_avatar');
                $user_info->saveAndCache();

                //user_log($user, $this->procedure, 'register', '【注册】通过%s注册，密码[%s]', $ctype['text'], $user->password);
            } else {
                throw new ApiException(ApiException::OauthNotRegister, trans('messages.not_register'));
            }
        } else {
            $user = Ucuser::from_cache($user_oauth->ucid);

            if($unionid && !$user_oauth->unionid) {
                $user_oauth->unionid = $unionid;
                $user_oauth->save();
            }
        }

        return $user;
    }
}