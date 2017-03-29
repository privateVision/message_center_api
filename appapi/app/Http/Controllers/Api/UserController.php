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
use App\Model\ProceduresExtend;

class UserController extends AuthController
{
    public function MessageAction(Request $request, Parameter $parameter) {
        return ;
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

        $old_password = $this->user->password;
        Event::onResetPassword($this->user, $new_password);
        user_log($this->user, $this->procedure, 'reset_password', '【重置用户密码】通过旧密码，旧密码[%s]，新密码[%s]', $old_password, $this->user->password);

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

        user_log($this->user, $this->procedure, 'bind_phone', '【绑定手机】手机号码{%s}', $mobile);

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

        user_log($this->user, $this->procedure, 'unbind_phone', '【解绑手机】手机号码{%s}', $mobile);

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

        $old_password = $this->user->password;
        Event::onResetPassword($this->user, $password);
        user_log($this->user, $this->procedure, 'reset_password', '【重置用户密码】通过手机验证码，手机号码{%s}，新密码[%s]，旧密码[%s]', $mobile, $this->user->password, $old_password);

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

    public function AttestAction(Request $request, Parameter $parameter) {
        $name = $parameter->tough('name');
        $card_id = $parameter->tough('card_id');

        $card_info = parse_card_id($card_id);
        if(!$card_info) {
            throw new ApiException(ApiException::Remind, "身份证号码不正确");
        }

        $this->user->real_name = $name;
        $this->user->card_id = $card_id;
        $this->user->birthday = $card_info['birthday'];
        $this->user->gender = $card_info['gender'];
        $this->user->asyncSave();

        user_log($this->user, $this->procedure, 'real_name_attest', '【实名认证】通过手机验证码，姓名:%s，身份证号码:%s', $name, $card_id);

        return ['result' => true];
    }
}