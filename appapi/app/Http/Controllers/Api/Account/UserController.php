<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use App\Jobs\AdtRequest;
use Illuminate\Http\Request;
use App\Parameter;

use App\Redis;
use App\Model\Ucuser;
use App\Model\_56GameBBS\Members as Member;
use App\Model\UcusersUUID;

class UserController extends Controller {
    
    use LoginAction, RegisterAction;
    
    const Type = 6;
    
    public function getLoginUser() {
        $imei = $this->parameter->get('_imei', '');
        $device_id = $this->parameter->get('_device_id', '');
        $username = $this->parameter->tough('username');
        $password = $this->parameter->tough('password', 'password');
        
        // --------- 登录错误限制
        $key = $device_id;
        if(!$key) {
            $key = getClientIp();
        }
        
        $key = md5($key .'_'. $username);
        $rediskey_lock = 'login_lock_' . $key;
        $rediskey_limit = 'login_limit_' . $key;
        
        if(Redis::get($rediskey_lock)) {
            throw new ApiException(ApiException::Remind, trans('messages.login_error'));
        }
        // --------- end
        
        // TODO 解决老用户会出现同时查找到两个用户的情况
        $user = Ucuser::where('uid', $username)->first();
        if(!$user) {
            $user = Ucuser::where('mobile', $username)->orWhere('email', $username)->first();
        }
        
        // TODO 数据迁移
        do {
            if(!isset($user)) break;
            
            $member = Member::where('uid', $user->ucid)->first();
            if(!$member) break;
            
            $user = Ucuser::from_cache($member->uid);
            if($user) {
                if(!$user->uid) $user->uid = $member->username;
                if(!$user->email) $user->email = $member->email;
                if(!$user->nickname) $user->nickname = rand(111111,999999);
                if(!$user->regip) $user->regip = $member->regip;
                if(!$user->regdate) $user->regdate = $member->regdate;
                if(!$user->password) {
                    $user->password = $member->password;
                    $user->salt = $member->salt;
                }
                
                $user->save();
            } else {
                $user = new Ucuser;
                $user->uid = $member->username;
                $user->email = $member->email ?: ($member->username . '@anfan.com');
                $user->nickname = $member->username;
                $user->password =$member->password;
                $user->salt =$member->salt;
                $user->regip = $member->regip;
                $user->regdate = $member->regdate;
                $user->rid = $this->parameter->tough('_rid');
                $user->pid = $this->procedur->pid;
                $user->imei = $imei;
                $user->device_id= $device_id;
                $user->save();
            }
        } while(false);
        
        if(!$user || !$user->checkPassword($password)) {
            // --------- 错误计数
            $count = Redis::get($rediskey_limit);
            if(!$count) {
                Redis::set($rediskey_limit, 1, 'EX', 300);
            } elseif($count >= 4) {
                Redis::set($rediskey_lock, 1, 'EX', 60);
            } else {
                Redis::incr($rediskey_limit);
            }
            // --------- end
            
            throw new ApiException(ApiException::Remind, trans('messages.login_fail'));
        }
        
        Redis::del($rediskey_limit);
        
        return $user;
    }
    
    public function getRegisterUser(){
        $username = $this->parameter->tough('username', 'username');
        $password = $this->parameter->tough('password', 'password');
        $imei     = $this->parameter->get("_imei");
        
        $isRegister  = Ucuser::where("mobile", $username)->orWhere('uid', $username)->count();
        
        if($isRegister) {
            throw new  ApiException(ApiException::Remind, trans('messages.already_register'));
        }
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = '暂无昵称';
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = getClientIp();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->procedur->pid;
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

        //登录加入通知队列
        dispatch((new AdtRequest(["imei"=>$imei,"gameid"=>$this->procedur->pid,"rid"=>$this->parameter->tough('_rid'),"ucid"=>$user->uid]))->onQueue('adtinit'));
        
        user_log($user, $this->procedure, 'register', '【注册】通过“用户名”注册，用户名(%s), 密码[%s]', $username, $user->password);
        
        return $user;
    }
    
    public function SMSResetPasswordAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        
        $user = Ucuser::where('mobile', $mobile)->first();
        
        if(!$user) {
            throw new ApiException(ApiException::Remind, trans('messages.mobile_not_bind'));
        }
        
        $code = smscode();

        try {
            // 给当前绑定的手机发短信，而不是当前输入的手机号
            send_sms($user->mobile, 0, 'reset_password', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }
        
        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }
    
    public function ResetPasswordAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');
        $new_password = $this->parameter->tough('password', 'password');
        
        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_smscode'));
        }
        
        $user = Ucuser::where('mobile', $mobile)->first();
        if(!$user) {
            throw new ApiException(ApiException::Remind, trans('messages.mobile_not_bind'));
        }
        
        $old_password = $user->password;
        
        $user->setPassword($new_password);
        $user->save();
        $user->updateCache();
        
        async_execute('expire_session', $user->ucid);
        user_log($user, $this->procedure, 'reset_password', '【重置密码】通过手机验证码重置，手机号码{%s}，旧密码[%s]，新密码[%s]', $mobile, $old_password, $user->password);
        
        return ['result' => true];
    }
    
    public function UsernameAction() {
        return ['username' => username()];
    }
}