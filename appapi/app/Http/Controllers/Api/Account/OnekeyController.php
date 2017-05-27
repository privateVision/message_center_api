<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\YunpianCallback;
use App\Model\UcusersUUID;

class OnekeyController extends Controller {

    use LoginAction;

    const Type = 4;

    public function getLoginUser() {
        $pid = $this->parameter->tough('_appid');
        $rid = $this->parameter->tough('_rid');
        $sms_token = $this->parameter->tough('sms_token');

        $yunpian_callback = YunpianCallback::where('text', $sms_token)->first();

        if(!$yunpian_callback) {
            throw new ApiException(ApiException::MobileNotRegister, trans('messages.not_accept_sms'));
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
        $user->nickname = '暂无昵称';
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = getClientIp();
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