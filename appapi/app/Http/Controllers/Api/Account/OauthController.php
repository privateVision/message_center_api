<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\UcuserOauth;
use App\Model\UcuserInfo;
use App\Model\UcusersUUID;

class OauthController extends Controller {

    use LoginAction, RegisterAction;

    const Type = 3;
    
    public function getRegisterUser() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid', "");
        
        if($type == 'weixin' && $unionid == '') throw new ApiException(ApiException::Error, "unionid不允许为空");

        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');

        $ctype = config("common.oauth.{$type}", false);
        if(!$ctype) {
            throw new ApiException(ApiException::Error, '未知的第三方登录类型，type='.$type);
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
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = '';
        $user->nickname = $nickname ?: $username;
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->save();
        
        $imei = $this->parameter->get('_imei', '');
        $device_id = $this->parameter->get('_device_id', '');
        if($imei || $device_id) {
            $ucusers_uuid =  new UcusersUUID();
            $ucusers_uuid->ucid = $user->ucid;
            $ucusers_uuid->imei = $imei;
            $ucusers_uuid->device_id= $device_id;
            $ucusers_uuid->asyncSave();
        }

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
        
        if($type == 'weixin' && $unionid == '') throw new ApiException(ApiException::Error, "unionid不允许为空");

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
                    throw new ApiException(ApiException::Error, '未知的第三方登录类型，type='.$type);
                }

                $nickname = $this->parameter->get('nickname');
                $avatar = $this->parameter->get('avatar');

                $username = username();
                $password = rand(100000, 999999);
                
                $user = new Ucuser;
                $user->uid = $username;
                $user->email = $username . "@anfan.com";
                $user->mobile = '';
                $user->nickname = $nickname ?: $username;
                $user->setPassword($password);
                $user->regtype = static::Type;
                $user->regip = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
                $user->rid = $this->parameter->tough('_rid');
                $user->pid = $this->parameter->tough('_appid');
                $user->regdate = time();
                $user->save();
                
                $imei = $this->parameter->get('_imei', '');
                $device_id = $this->parameter->get('_device_id', '');
                if($imei || $device_id) {
                    $ucusers_uuid =  new UcusersUUID();
                    $ucusers_uuid->ucid = $user->ucid;
                    $ucusers_uuid->imei = $imei;
                    $ucusers_uuid->device_id= $device_id;
                    $ucusers_uuid->asyncSave();
                }

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

                user_log($user, $this->procedure, 'register', '【注册】通过%s注册，密码[%s]', $ctype['text'], $user->password);
            } else {
                throw new ApiException(ApiException::OauthNotRegister, "尚未注册");
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