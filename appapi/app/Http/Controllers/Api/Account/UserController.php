<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Redis;
use App\Model\Ucuser;
use App\Model\_56GameBBS\Members as Member;

class UserController extends Controller {

    use LoginAction, RegisterAction;

    public function getLoginUser() {
        $username = $this->parameter->tough('username');
        $password = $this->parameter->tough('password', 'password');
        $device_id = $this->parameter->get('_device_id');

        // --------- 登陆错误限制
        $key = $device_id;
        if(!$key) {
            $key = $this->request->ip();
        }

        $key = md5($key .'_'. $username);
        $rediskey_lock = 'login_lock_' . $key;
        $rediskey_limit = 'login_limit_' . $key;

        if(Redis::get($rediskey_lock)) {
            throw new ApiException(ApiException::Remind, "错误次数太多，请稍后再试");
        }
        // --------- end
        
        // todo:数据迁移
        $user = Ucuser::where('uid', $username)->orWhere('mobile', $username)->orWhere('email', $username)->first();
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
                $user->pid = $this->parameter->tough('_appid');
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

            throw new ApiException(ApiException::Remind, "用户名或者密码不正确");
        }

        Redis::del($rediskey_limit);

        return $user;
    }
    
    public function getRegisterUser(){
        $username = $this->parameter->tough('username', 'username');
        $password = $this->parameter->tough('password', 'password');

        $isRegister  = Ucuser::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登录");
        }

        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = '暂无昵称';
        $user->setPassword($password);
        $user->regip = $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->save();

        user_log($user, $this->procedure, 'register', '【注册】通过“用户名”注册，用户名(%s), 密码[%s]', $username, $user->password);
        
        return $user;
    }
    
    public function SMSResetPasswordAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');

        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
    
        if(!$user || $user->mobile =="") {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $code = smscode();

        try {
            send_sms($mobile, 0, 'reset_password', ['#code#' => $code], $code);
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
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if(!$user) {
            throw new ApiException(ApiException::Remind, '手机号码尚未绑定');
        }

        $old_password = $user->password;

        $user->setPassword($new_password);
        $user->save();
        
        user_log($user, $this->procedure, 'reset_password', '【重置密码】通过手机验证码重置，手机号码{%s}，旧密码[%s]，新密码[%s]', $mobile, $old_password, $user->password);

        return ['result' => true];
    }

    public function UsernameAction() {
        return ['username' => username()];
    }
}