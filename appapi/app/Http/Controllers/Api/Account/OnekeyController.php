<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\YunpianCallback;

class OnekeyController extends Controller {

    use LoginAction;

    public function getLoginUser() {
        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        $sms_token = $this->parameter->tough('sms_token');

        $yunpian_callback = YunpianCallback::where('text', $sms_token)->first();

        if(!$yunpian_callback) {
            throw new ApiException(ApiException::MobileNotRegister, '服务器等待收到短信...');
        }

        $mobile = $yunpian_callback->mobile;

        // 登录
        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            return $user;
        }
        
        // 注册
        $username = username();
        $password = rand(100000, 999999);
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->mobile = $mobile;
        $user->nickname = '未设置';
        $user->setPassword($password);
        $user->regip = $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->save();

        user_log($user, $this->procedure, 'register', '【注册】通过“手机号码一键登录”注册，手机号码{%s}, 密码[%s]', $mobile, $user->password);

        try {
            send_sms($mobile, 0, 'mobile_register', ['#username#' => $username, '#password#' => $password]);
        } catch (\App\Exceptions\Exception $e) {
            // throw new ApiException(ApiException::Remind, $e->getMessage());
        }
        
        return $user;
    }
    
    public function SMSTokenAction() {
        $config = config('common.smsconfig');
        return [
            'sms_token' => uuid(), 
            'send_to' => $config['receiver']
        ];
    }
}