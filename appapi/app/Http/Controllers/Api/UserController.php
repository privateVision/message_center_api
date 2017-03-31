<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\Orders;
use App\Model\UcuserRole;
use App\Model\ProceduresZone;
use App\Model\ProceduresExtend;
use App\Model\UcuserSub;
use App\Model\UcuserOauth;
use App\Model\UcuserInfo;

class UserController extends AuthController
{
    public function InfoAction(Request $request, Parameter $parameter) {
        $user_info = UcuserInfo::from_cache($this->user->ucid);

        return [
            'uid' => $this->user->ucid,
            'username' => $this->user->uid,
            'nickname' => $this->user->nickname,
            'mobile' => $this->user->mobile,
            'email' => $this->user->email,
            'balance' => $this->user->balance,
            'gender' => $user_info && $user_info->gender ? (int)$user_info->gender : 0,
            'birthday' => $user_info && $user_info->birthday ? (string)$user_info->birthday : "",
            'address' => $user_info && $user_info->address ? (string)$user_info->address : "",
            'avatar' => $user_info && $user_info->avatar ? (string)$user_info->avatar : env('default_avatar'),
            'real_name' => $user_info && $user_info->real_name ? (string)$user_info->real_name : "",
            'card_no' => $user_info && $user_info->card_no ? (string)$user_info->card_no : "",
            'exp' => $user_info && $user_info->exp ? (int)$user_info->exp : 0,
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'score' => $user_info && $user_info->score ? (int)$user_info->score : 0,
            'is_real' => $user_info && $user_info->isReal(),
            'is_adult' => $user_info && $user_info->isAdult(),
        ];
    }

    public function RechargeAction(Request $request, Parameter $parameter) {
        $order = $this->user->orders()->where('vid', '>=', 100)->where('status', Orders::Status_Success)->where('hide', 0)->get();

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
        $order = $this->user->orders()->where('vid', '<', 100)->where('status', Orders::Status_Success)->where('hide', 0)->get();

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
        $old_password = $parameter->tough('old_password', 'password');
        $new_password = $parameter->tough('new_password', 'password');

        if(!$this->user->checkPassword($old_password)) {
            throw new ApiException(ApiException::Remind, "旧的密码不正确");
        }

        $old_password = $this->user->password;
        user_log($this->user, $this->procedure, 'reset_password', '【重置用户密码】通过旧密码，旧密码[%s]，新密码[%s]', $old_password, $this->user->password);

        return ['result' => true];
    }

    public function SMSBindPhoneAction(Request $request, Parameter $parameter) {
        $mobile = $parameter->tough('mobile', 'mobile');

        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, "手机号码已经绑定了其它账号");
            } else {
                throw new ApiException(ApiException::Remind, "该账号已经绑定了这个手机号码");
            }
        }

        $code = smscode();

        try {
            send_sms($mobile, 0, 'bind_phone', ['#code#' => $code], $code);
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
        
        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
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
            send_sms($mobile, 0, 'unbind_phone', ['#code#' => $code], $code);
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
            send_sms($mobile, 0, 'reset_password', ['#code#' => $code], $code);
        } catch (\App\Exceptions\Exception $e) {
            throw new ApiException(ApiException::Remind, $e->getMessage());
        }

        return [
            'code' => md5($code . $this->procedure->appkey())
        ];
    }

    public function PhoneResetPasswordAction(Request $request, Parameter $parameter) {
        $code = $parameter->tough('code', 'smscode');
        $password = $parameter->tough('password', 'password');

        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法使用该方式重置密码");
        }

        $mobile = $this->user->mobile;

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $old_password = $this->user->password;
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
        $card_no = $parameter->tough('card_id');

        $card_info = parse_card_id($card_no);
        if(!$card_info) {
            throw new ApiException(ApiException::Remind, "身份证号码不正确");
        }

        $user_info = UcuserInfo::from_cache($this->user->ucid);
        if(!$user_info) {
            $user_info = new UcuserInfo;
            $user_info->ucid = $this->user->ucid;
        }

        $user_info->real_name = $name;
        $user_info->card_no = $card_no;
        $user_info->birthday = $card_info['birthday'];
        $user_info->gender = $card_info['gender'];
        $user_info->asyncSave();

        user_log($this->user, $this->procedure, 'real_name_attest', '【实名认证】通过手机验证码，姓名:%s，身份证号码:%s', $name, $card_no);

        return ['result' => true];
    }

    public function BindOauthAction(Request $request, Parameter $parameter) {
        $openid = $parameter->tough('openid');
        $type = $parameter->tough('type');
        $unionid = $parameter->get('unionid');
        $nickname = $parameter->get('nickname');
        $avatar = $parameter->get('avatar');

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
            $user_oauth = new UcuserOauth;
            $user_oauth->ucid = $this->user->ucid;
            $user_oauth->type = $type;
            $user_oauth->openid = $openid;
            $user_oauth->unionid = $unionid;
            $user_oauth->saveAndCache();

            if($avatar) {
                $user_info = UcuserInfo::from_cache($this->user->ucid);
                if(!$user_info) {
                    $user_info = new UcuserInfo;
                    $user_info->ucid = $this->user->ucid;
                }

                if(!$user_info->avatar) {
                    $user_info->avatar = $avatar;
                    $user_info->saveAndCache();
                }
            }
        } elseif($user_oauth->ucid != $this->user->ucid) {
            throw new ApiException(ApiException::Remind, config("common.oauth.{$type}.text", '第三方') . "已经绑定了其它账号");
        }

        return ['result' => true];
    }
}