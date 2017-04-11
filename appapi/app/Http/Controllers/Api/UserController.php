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
    public function InfoAction() {
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
            'province' => $user_info && $user_info->province ? (string)$user_info->province : "",
            'city' => $user_info && $user_info->city ? (string)$user_info->city : "",
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

    public function BindListAction() {
        $data = [];

        $oauth = UcuserOauth::where('ucid', $this->user->ucid)->pluck('type');
        foreach($oauth as $v) {
            $data[$v]['is_bind'] = true;
        }

        if($this->user->mobile) {
            $data['mobile']['is_bind'] = true;
        }

        return $data;
    }

    public function SetAction() {
        $nickname = $this->parameter->get('nickname');
        $province = $this->parameter->get('province');
        $city = $this->parameter->get('city');
        $address = $this->parameter->get('address');
        $gender = $this->parameter->get('gender');
        $birthday = $this->parameter->get('birthday', function($v) {
            if(!preg_match('/^\d{8}$/', $v)) {
                throw new ApiException(ApiException::Remind, "生日格式不正确，yyyy-mm-dd");
            }

            $y = substr($v, 0, 4);
            $m = substr($v, 4, 2);
            $d = substr($v, 6, 2);

            $interval = date_diff(date_create("{$y}-{$m}-{$d}"), date_create(date('Y-m-d')));

            if(@$interval->y > 80 || $interval->y < 1) {
                throw new ApiException(ApiException::Remind, "生日不是一个有效的日期");
            }

            return $v;
        });

        $user_info = UcuserInfo::from_cache($this->user->ucid);
        if(!$user_info) {
            $user_info = new UcuserInfo();
            $user_info->ucid = $this->user->ucid;
        }

        if($nickname) {
            $this->user->nickname = $nickname;
        }

        if($birthday) {
            $user_info->birthday = $birthday;
        }

        if($province) {
            $user_info->province = $province;
        }

        if($city) {
            $user_info->city = $city;
        }

        if($address) {
            $user_info->address = $address;
        }

        if($gender) {
            $user_info->gender = $gender;
        }

        $this->user->save();
        $user_info->save();

        return ['result' => true];
    }

    public function RechargeAction() {
        $page = $this->parameter->get('page', 1);
        $limit = $this->parameter->get('count', 10);

        $offset = max(0, ($page - 1) * $limit);

        $order = Orders::whereIsF();
        $order = $order->where('ucid', $this->user->ucid);
        $order = $order->where('hide', 0);
        $order = $order->where('status', '!=', Orders::Status_WaitPay);
        $count = $order->count();
        $order = $order->orderBy('id', 'desc');
        $order = $order->take($limit)->skip($offset)->get();

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

        return ['count' => $count, 'list' => $data];
    }

    public function ConsumeAction() {
        $page = $this->parameter->get('page', 1);
        $limit = $this->parameter->get('count', 10);

        $offset = max(0, ($page - 1) * $limit);

        $order = Orders::whereIsNotF();
        $order = $order->where('ucid', $this->user->ucid);
        $order = $order->where('hide', 0);
        $order = $order->where('status', '!=', Orders::Status_WaitPay);
        $count = $order->count();
        $order = $order->orderBy('id', 'desc');
        $order = $order->take($limit)->skip($offset)->get();

        $data = [];
        foreach($order as $v) {
            //if($v->is_f()) continue;
            $data[] = [
                'order_id' => $v->sn,
                'fee' => $v->fee,
                'subject' => $v->subject,
                'otype' => 0, // todo: 这是什么鬼？
                'createTime' => strtotime($v->createTime),
                'status' => $v->status,
            ];
        }

        return ['count' => $count, 'list' => $data];
    }

    public function HideOrderAction() {
        $sn = $this->parameter->tough('order_id');
        Orders::where('sn', $sn)->update(['hide' => true]);
        return ['result' => true];
    }

    public function BalanceAction() {
        return ['balance' => $this->user->balance];
    }

    public function ByOldPasswordResetAction() {
        $old_password = $this->parameter->tough('old_password', 'password');
        $new_password = $this->parameter->tough('new_password', 'password');

        if(!$this->user->checkPassword($old_password)) {
            throw new ApiException(ApiException::Remind, "旧的密码不正确");
        }

        $old_password = $this->user->password;
        $this->user->setPassword($new_password);
        $this->user->save();

        user_log($this->user, $this->procedure, 'reset_password', '【重置用户密码】通过旧密码，旧密码[%s]，新密码[%s]', $old_password, $this->user->password);

        return ['result' => true];
    }

    public function SMSBindPhoneAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');

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

    public function BindPhoneAction() {
        // todo: 以前绑定手机号绑定邮箱走的同一个接口
        $mobile = $this->request->input('mobile');
        if(preg_match('/^[\w\d\-\_\.]+@\w+(\.\w+)+$/', $mobile)) {
            throw new ApiException(ApiException::Remind, "该功能已停用");
        }
        // ---- end ----

        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');

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

    public function SMSUnbindPhoneAction() {
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

    public function UnbindPhoneAction() {
        $code = $this->parameter->tough('code', 'smscode');

        if($this->user->mobile) {
            $mobile = $this->user->mobile;

            if(!verify_sms($mobile, $code)) {
                throw new ApiException(ApiException::Remind, "手机号码解绑失败，验证码不正确，或已过期");
            }
            
            $this->user->mobile = '';
            $this->user->save();

            user_log($this->user, $this->procedure, 'unbind_phone', '【解绑手机】手机号码{%s}', $mobile);
        }

        return [
            'result' => true,
            'username' => $this->user->uid,
            'text' => '解绑成功，您不可以再使用该手机号码登陆，请使用用户名登陆，用户名：'. $this->user->uid,
        ];
    }

    public function SMSPhoneResetPasswordAction() {
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

    public function PhoneResetPasswordAction() {
        $code = $this->parameter->tough('code', 'smscode');
        $password = $this->parameter->tough('password', 'password');

        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, "还未绑定手机号码，无法使用该方式重置密码");
        }

        $mobile = $this->user->mobile;

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        $old_password = $this->user->password;
        $this->user->setPassword($password);
        $this->user->save();

        user_log($this->user, $this->procedure, 'reset_password', '【重置密码】通过手机验证码，手机号码{%s}，新密码[%s]，旧密码[%s]', $mobile, $this->user->password, $old_password);

        return ['result' => true];
    }

    public function ReportRoleAction() {
        $zone_id = $this->parameter->tough('zone_id');
        $zone_name = $this->parameter->tough('zone_name');
        $role_id = $this->parameter->tough('role_id');
        $role_level = $this->parameter->tough('role_level');
        $role_name = $this->parameter->tough('role_name');

        $pid = $this->parameter->tough('_appid');

        $this->session->zone_id = $zone_id;
        $this->session->zone_name = $zone_name;
        $this->session->save();

        async_execute('report_role', $this->user->ucid, $pid, $this->session->user_sub_id, $zone_id, $zone_name, $role_id, $role_name, $role_level);

        return ['result' => true];
    }

    public function AttestAction() {
        $name = $this->parameter->tough('name');
        $card_no = $this->parameter->tough('card_id');

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

        user_log($this->user, $this->procedure, 'real_name_attest', '【实名认证】姓名:%s，身份证号码:%s', $name, $card_no);

        return ['result' => true];
    }

    public function BindOauthAction() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid');
        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');
        $forced = $this->parameter->get('forced');

        $count = UcuserOauth::where('type', $type)->where('ucid', $this->user->ucid)->count();
        if($count > 0) {
            throw new ApiException(ApiException::Remind, "账号已经绑定了" . config("common.oauth.{$type}.text", '第三方'));
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
            if($user_oauth->ucid == $this->user->ucid) {
                return ['result' => true];
            }

            if($forced == 0) {
                throw new ApiException(ApiException::AlreadyBindOauthOther, config("common.oauth.{$type}.text", '第三方') . "已经绑定了其它账号");
            }

            $user_oauth->ucid = $this->user->ucid;
            $user_oauth->save();
        } else {
            $user_oauth = new UcuserOauth;
            $user_oauth->ucid = $this->user->ucid;
            $user_oauth->type = $type;
            $user_oauth->openid = $openid;
            $user_oauth->unionid = $unionid;
            $user_oauth->saveAndCache();
        }

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

        user_log($this->user, $this->procedure, 'bind_oauth', '【绑定平台帐号】%s', config("common.oauth.{$type}.text", '第三方'));

        return ['result' => true];
    }

    public function UnbindOauthAction() {
        $type = $this->parameter->tough('type');

        $count = UcuserOauth::where('type', '!=', $type)->where('ucid', $this->user->ucid)->count();
        if($count == 0 && $this->user->mobile == "") {
            throw new ApiException(ApiException::Remind, "为了防止遗忘账号，请绑定手机或者其他社交账号后再解除绑定");
        }

        UcuserOauth::where('type', $type)->where('ucid', $this->user->ucid)->delete();

        user_log($this->user, $this->procedure, 'unbind_oauth', '【解绑平台帐号】%s', config("common.oauth.{$type}.text", '第三方'));

        return ['result' => true];
    }

    public function SetAvatarAction() {
        $type = $this->parameter->tough('type');
        $avatar = $this->parameter->tough('avatar');

        $user_info = UcuserInfo::from_cache($this->user->ucid);
        if(!$user_info) {
            $user_info = new UcuserInfo;
            $user_info->ucid = $this->user->ucid;
        }

        $avatar_url = null;

        if($type == 'url') {
            $avatar_url = $avatar;
        } elseif ($type == 'bindata') {
            $filename = sprintf('avatar/%d.png', $this->user->ucid);
            $filepath = base_path('storage/uploads/') . $filename;
            $avatar_data = base64_decode($avatar);

            $fp = fopen($filepath, 'wb');
            fwrite($fp, $avatar_data);
            fclose($fp);

            try {
                $avatar_url = upload_to_cdn($filename, $filepath);
            } catch(\App\Exceptions\Exception $e) {
                throw new ApiException(ApiException::Remind, '头像上传失败：' . $e->getMessage());
            }
        }

        if($avatar_url) {
            $user_info->avatar = $avatar_url;
            $user_info->save();
        }

        return [
            'result' => $avatar_url ? true : false,
            'avatar' => $avatar_url,
        ];
    }

    public function SetUsernameAction() {
        $username = $this->parameter->tough('username');

        $user = Ucuser::where('uid', $username)->orWhere('mobile', $username)->orWhere('email', $username)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, '设置失败，用户名已被占用');
            }
        } else {
            $this->user->uid = $username;
            $this->user->save();
        }

        return ['result' => true];
    }

    public function SetNicknameAction() {
        $nickname = $this->parameter->tough('nickname');
        $this->user->nickname = $nickname;
        $this->user->save();

        return ['result' => true];
    }

    public function EventAction() {
        $event = $this->parameter->tough('event');
        return ['result' => true];
    }
}