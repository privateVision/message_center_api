<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Event;
use App\Model\User;
use App\Model\Orders;
use App\Model\UserRole;
use App\Model\ProceduresZone;

class UserController extends AuthController
{
    public function MessageAction(Request $request, Parameter $parameter) {
        return ;
    }

    public function LogoutAction(Request $request, Parameter $parameter) {
        Event::onLogoutAfter($this->user);
        return ['result' => true];
    }

    public function RechargeAction(Request $request, Parameter $parameter) {
        $order = $this->user->orders()->where('vid', env('APP_SELF_ID'))->where('status', Orders::Status_Success)->where('hide', 0)->get();

        $data = [];
        foreach($order as $v) {
            $data[] = [
                'order_id' => $v->sn,
                'fee' => $v->fee,
                'subject' => $v->subject,
                'otype' => 0, // todo: 这是什么鬼？
                'createTime' => strtotime($v->createTime),
                'status' => $v->status,
            ];
        }

        return $data;
    }

    public function ConsumeAction(Request $request, Parameter $parameter) {
        $order = $this->user->orders()->where('vid', '!=', env('APP_SELF_ID'))->where('status', Orders::Status_Success)->where('hide', 0)->get();

        $data = [];
        foreach($order as $v) {
            $data[] = [
                'order_id' => $v->sn,
                'fee' => $v->fee,
                'subject' => $v->subject,
                'otype' => 0, // todo: 这是什么鬼？
                'createTime' => strtotime($v->createTime),
                'status' => $v->status,
            ];
        }

        return $data;
    }

    public function HideOrderAction(Request $request, Parameter $parameter) {
        $sn = $parameter->tough('order_id');
        Orders::where('sn', $sn)->update(['hide' => true]);
        return ['result' => true];
    }

    public function BalanceAction(Request $request, Parameter $parameter) {
        return ['balance' => $this->user->balance];
    }

    public function ByOldPasswordResetAction(Request $request, Parameter $parameter) {
        $old_password = $parameter->tough('old_password');
        $new_password = $parameter->tough('new_password');

        if(!$this->user->checkPassword($old_password)) {
            throw new ApiException(ApiException::Remind, "旧的密码不正确");
        }

        $this->user->password = $new_password;
        $this->user->save();

        return ['result' => true];
    }

    public function SMSBindPhoneAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');

        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, "手机号码已经绑定了其它账号");
            } else {
                throw new ApiException(ApiException::Remind, "该账号已经绑定了这个手机号码");
            }
        }

        $code = smscode();

        try {
            send_sms($mobile, env('APP_ID'), 'bind_phone', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function BindPhoneAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');
        $code = $parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "绑定失败，验证码不正确，或已过期");
        }

        if($this->user->mobile) {
            throw new ApiException(ApiException::Remind, "该账号已经绑定了手机号码");
        }
        
        $user = User::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, "手机号码已经绑定了其它账号");
            }
        } else {
            $this->user->mobile = $mobile;
            $this->user->save();
        }

        return ['result' => true];
    }

    public function SMSUnbindPhoneAction(Request $request, Parameter $parameter) {
        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法解绑");
        }

        $mobile = $this->user->mobile;

        $code = smscode();

        try {
            send_sms($mobile, env('APP_ID'), 'unbind_phone', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function UnbindPhoneAction(Request $request, Parameter $parameter) {
        $code = $parameter->tough('code', 'smscode');

        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法解绑");
        }

        $mobile = $this->user->mobile;

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "手机号码解绑失败，验证码不正确，或已过期");
        }
        
        $this->user->mobile = '';
        $this->user->save();

        return [
            'result' => true,
            'username' => $this->user->uid,
            'text' => '解绑成功，您不可以再使用该手机号码登陆，请使用用户名登陆，用户名：'. $this->user->uid,
        ];
    }

    public function SMSPhoneResetPasswordAction(Request $request, Parameter $parameter) {
        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法使用该方式重置密码");
        }

        $mobile = $this->user->mobile;

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

    public function PhoneResetPasswordAction(Request $request, Parameter $parameter) {
        $code = $parameter->tough('code', 'smscode');
        $password = $parameter->tough('password');

        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法使用该方式重置密码");
        }

        $mobile = $this->user->mobile;

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $this->user->password = $password;
        $this->user->save();

        return ['result' => true];
    }

    public function ReportRoleAction(Request $request, Parameter $parameter) {
        $zone_id = $parameter->tough('zone_id');
        $zone_name = $parameter->tough('zone_name');
        $role_id = $parameter->tough('role_id');
        $role_level = $parameter->tough('role_level');
        $role_name = $parameter->tough('role_name');

        $pid = $parameter->tough('_appid');

        async_execute('report_role', $this->user->ucid, $pid, $this->session->user_sub_id, $zone_id, $zone_name, $role_id, $role_name, $role_level);

        return ['result' => true];
    }
    // --------------------------------------------------------------------------------------------------------------------------------
    
    const SMS_LIMIT = 3;

    /*
 * 获取短息验证码
 * @param  uid
 * $param mobile
 * return $code 生成的短信验证码
 * */
    public function getAuthCodeAction(Request $request,Parameter $parameter){
        $code = smscode();
        $content  = trans('messages.phone_unbind_code').$code;
        send_sms($this->session->mobile,$content,$code);
    }

    /*
     * 手机解绑
     * @param $mobile
     * */

    public function unbindAction(Request $request,Parameter $parameter){
        $mobile = $this->session->mobile;
        $code = $request->input('code');

        if(!preg_match('/^\d{6}$/',$code)) throw new ApiException(ApiException::Remind, "验证码格式不正确！");

        $chars = 'abcdefghjkmnpqrstuvwxy';

        if($this->uuser->mobile == $this->user->uid){
            $username = User::username();
            $this->user->ucenter_members->username = $username;
            $this->user->ucenter_members->save();
            $content = trans_choice('messages.phone_unbind_code', $username);
            send_sms($this->user->mobile,$content);
            $this->user->uid= $username;
            $this->user->save();
        }else{

        }

        UcenterMembers::where("username",$this->user->uid)->get();
    }

    /*
     * 手机绑定短信
     * */
    public function bind(Request $request ,Parameter $parameter){
        $mobile = $request->input("mobile");

        if(!check_mobile($mobile)){
            throw new ApiException(ApiException::Remind,trans("messages.mobile_type_error"));
        }

        $code = smscode();
        $content = trans("messages.sms_code").$code;

        //发送短信验证码限制 防止短信炸弹
        $rkey = $mobile.":".$this->code;
        $numj = Cache::get($rkey);

        if(!$numj ) {
            Cache::store("redis")->put($rkey, 1, 60 * 24); //保存一天 一天内发送短信的次数的限制
        }
        if($numj == self::SMS_LIMIT){
            throw  new ApiException(ApiException::Remind,trans("messages.sms_limit_code"));
        }else{
            Cache::increment($rkey); //发送短信次数增加
        }

        send_sms($mobile, $content,$code);
        return ['code'=>$code];
    }

    /*
     * 获取用户的钱包的信息
     * */

    public function walletAction(Request $request,Parameter $parameter){
        return  ["wallet"=>$this->user->balance];
    }

}