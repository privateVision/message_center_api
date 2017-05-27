<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Model\Ucuser;
use App\Model\Orders;
use App\Model\UcuserOauth;
use App\Model\UcuserInfo;
use App\Model\Retailers;

class UserController extends AuthController
{
    public function InfoAction() {
        $user_info = UcuserInfo::from_cache($this->user->ucid);

        $retailers = null;
        if($this->user->rid) {
            $retailers = Retailers::find($this->user->rid);
        }

        // 读取用户第三方绑定状态
        $config = config('common.oauth');
        $bindlist = [];

        foreach($config as $k => $v) {
            $bindlist[$k]['is_bind'] = false;
        }

        $oauth = UcuserOauth::where('ucid', $this->user->ucid)->get();
        foreach($oauth as $v) {
            $bindlist[$v->type]['is_bind'] = true;
            $bindlist[$v->type]['openid'] = $v->openid;
            $bindlist[$v->type]['unionid'] = $v->unionid;
        }

        $bindlist['mobile']['is_bind'] = $this->user->mobile ? true : false;
        if($bindlist['mobile']['is_bind']) {
            $bindlist['mobile']['unionid'] = $this->user->mobile;
            $bindlist['mobile']['openid'] = $this->user->mobile;
        }

        $bindlist['password']['is_bind'] = $this->user->regtype == 6;// TODO App\Http\Controllers\Api\Account\UserController::Type;

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
            'reg_time' => $this->user->regdate,
            'regtype' => $this->user->regtype,
            'rid' => $this->user->rid,
            'rtype' => $retailers ? $retailers->rtype : 0,
            'bindlist' => $bindlist,
        ];
    }

    public function BindListAction() {
        $config = config('common.oauth');
        $data = [];

        foreach($config as $k => $v) {
            $data[$k]['is_bind'] = false;
        }

        $oauth = UcuserOauth::where('ucid', $this->user->ucid)->get();
        foreach($oauth as $v) {
            $data[$v->type]['is_bind'] = true;
            $data[$v->type]['openid'] = $v->openid;
            $data[$v->type]['unionid'] = $v->unionid;
        }

        $data['mobile']['is_bind'] = $this->user->mobile ? true : false;
        if($data['mobile']['is_bind']) {
            $data['mobile']['unionid'] = $this->user->mobile;
            $data['mobile']['openid'] = $this->user->mobile;
        }

        $data['password']['is_bind'] = $this->user->regtype == 6;// TODO App\Http\Controllers\Api\Account\UserController::Type;

        return $data;
    }

    public function SetAction() {
        $nickname = $this->parameter->get('nickname', null, 'nickname');
        $province = $this->parameter->get('province');
        $city = $this->parameter->get('city');
        $address = $this->parameter->get('address');
        $gender = $this->parameter->get('gender');
        $birthday = $this->parameter->get('birthday', '');

        if($birthday && !preg_match('/^\d{8}$/', $birthday)) {
            throw new ApiException(ApiException::Remind, trans('messages.birthday_format_error'));
        }

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

        if($address !== null) {
            $user_info->address = $address;
        }

        if($gender === '0' || $gender === '1' || $gender === '2') {
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
                'otype' => 0,
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
        $order = $order->where('vid', $this->procedure->pid);
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

    public function HideOrderAction() {
        $sn = $this->parameter->tough('order_id');

        $order = Orders::from_cache($sn);
        if($order) {
            $order->hide = true;
            $order->save();
        }

        return ['result' => true];
    }

    public function BalanceAction() {
        return [
            'balance' => $this->user->balance,
        ];
    }

    public function ByOldPasswordResetAction() {
        $old_password = $this->parameter->tough('old_password', 'password');
        $new_password = $this->parameter->tough('new_password', 'password');

        if(!$this->user->checkPassword($old_password)) {
            throw new ApiException(ApiException::Remind, trans('messages.oldpassword_error'));
        }

        $old_password = $this->user->password;
        $this->user->setPassword($new_password);
        $this->user->save();

        async_execute('expire_session', $this->user->ucid);
        user_log($this->user, $this->procedure, 'reset_password', '【重置用户密码】通过旧密码，旧密码[%s]，新密码[%s]', $old_password, $this->user->password);

        return ['result' => true];
    }

    public function SMSBindPhoneAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');

        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();

        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, trans('messages.mobile_bind_other'));
            } elseif($this->user->mobile == $mobile) {
                throw new ApiException(ApiException::Remind, trans('messages.mobile_already_bind'));
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
            throw new ApiException(ApiException::Remind, trans('messages.func_disable'));
        }

        // ---- end ----

        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_smscode'));
        }

        if($this->user->mobile) {
            throw new ApiException(ApiException::Remind, trans('messages.user_already_bind_mobile'));
        }

        $user = Ucuser::where('uid', $mobile)->orWhere('mobile', $mobile)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, trans('messages.mobile_bind_other'));
            } elseif(empty($this->user->mobile)) {
                $this->user->mobile = $mobile;
                $this->user->save();
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
            throw new ApiException(ApiException::Remind, trans('messages.not_bind_onunbind'));
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
        $username = $this->parameter->get('username', null, 'username'); // 解绑可以同时设置uid

        if($this->user->mobile) {
            $mobile = $this->user->mobile;

            if(!verify_sms($mobile, $code)) {
                throw new ApiException(ApiException::Remind, trans('messages.invalid_smscode'));
            }

            if($this->user->mobile == $this->user->uid) {
                if(!$username) {
                    throw new ApiException(ApiException::Remind, trans('messages.reset_username_onunbind'));
                }

                $_user = Ucuser::where('uid', $username)->orWhere('mobile', $username)->orWhere('email', $username)->first();
                if(!$_user || $_user->ucid == $this->user->ucid) {
                    $this->user->uid = $username;
                } else {
                    throw new ApiException(ApiException::Remind, trans('messages.username_exists_onbind'));
                }
            }
            $this->user->mobile = '';
            $this->user->save();

            user_log($this->user, $this->procedure, 'unbind_phone', '【解绑手机】手机号码{%s}', $mobile);
        }

        return [
            'result' => true,
            'username' => $this->user->uid,
            'text' => '解绑成功',
        ];
    }

    public function SMSPhoneResetPasswordAction() {
        if(!$this->user->mobile) {
            throw new ApiException(ApiException::Remind, trans('messages.not_reset_password_unbind_mobile'));
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
            throw new ApiException(ApiException::Remind, trans('messages.not_reset_password_unbind_mobile'));
        }

        $mobile = $this->user->mobile;

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_smscode'));
        }

        $old_password = $this->user->password;
        $this->user->setPassword($password);
        $this->user->save();

        async_execute('expire_session', $this->user->ucid);
        user_log($this->user, $this->procedure, 'reset_password', '【重置密码】通过手机验证码重置，手机号码{%s}，旧密码[%s]，新密码[%s]', $mobile, $old_password, $this->user->password);

        return ['result' => true];
    }

    public function ReportRoleAction() {
        $zone_id = $this->parameter->tough('zone_id');
        $zone_name = $this->parameter->tough('zone_name');
        $role_id = $this->parameter->tough('role_id');
        $role_level = $this->parameter->tough('role_level');
        $role_name = $this->parameter->tough('role_name');

        $pid = $this->procedure->pid;

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
            throw new ApiException(ApiException::Remind, trans('messages.cardno_error'));
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
        $user_info->save();

        user_log($this->user, $this->procedure, 'real_name_attest', '【实名认证】姓名:%s，身份证号码:%s', $name, $card_no);

        return ['result' => true];
    }

    public function BindOauthAction() {
        $openid = $this->parameter->tough('openid');
        $type = $this->parameter->tough('type');
        $unionid = $this->parameter->get('unionid', "");
        $nickname = $this->parameter->get('nickname');
        $avatar = $this->parameter->get('avatar');
        $forced = $this->parameter->get('forced');

        if($type == 'weixin' && $unionid == '') throw new ApiException(ApiException::Error, trans('messages.unionid_empty'));

        $count = UcuserOauth::where('type', $type)->where('ucid', $this->user->ucid)->count();
        if($count > 0) {
            throw new ApiException(ApiException::Remind, trans('messages.3th_already_bind', ['type' => config("common.oauth.{$type}.text")]));
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
            if($user_oauth->ucid == $this->user->ucid) {
                return ['result' => true];
            }

            if($forced == 0) {
                throw new ApiException(ApiException::AlreadyBindOauthOther, trans('messages.3th_already_bind_other', ['type' => config("common.oauth.{$type}.text")]));
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
        if($count == 0 && $this->user->mobile == "" && $this->user->regtype != 6) {
            throw new ApiException(ApiException::Remind, trans('messages.3th_unbind_error'));
        }

        $user_oauth = UcuserOauth::where('type', $type)->where('ucid', $this->user->ucid)->first();
        if($user_oauth) {
            $user_oauth->delete();
        }

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
            $fp = fopen($filepath, 'w+');
            fwrite($fp, $avatar_data);
            fclose($fp);

            try {
                $avatar_url = upload_to_cdn($filename, $filepath,true);
                $flush = updateQnCache($avatar_url); //更新七牛文件缓存
            } catch(\App\Exceptions\Exception $e) {
                throw new ApiException(ApiException::Remind, trans('messages.avatar_set_error', ['eMsg' => $e->getMessage()]));
            }
        }

        if($avatar_url) {
            $user_info->avatar = $avatar_url;
            $user_info->save();
        }

        return [
            'result' => $avatar_url ? true : false,
            'avatar' => $avatar_url,
            "flush"  => $flush->ok()
        ];
    }

    public function SetUsernameAction() {
        $username = $this->parameter->tough('username');

        $user = Ucuser::where('uid', $username)->orWhere('mobile', $username)->orWhere('email', $username)->first();
        if($user) {
            if($user->ucid != $this->user->ucid) {
                throw new ApiException(ApiException::Remind, trans('messages.username_exists_onset'));
            }
        } else {
            $this->user->uid = $username;
            $this->user->save();
        }

        return ['result' => true];
    }

    public function SetNicknameAction() {
        $nickname = $this->parameter->tough('nickname', 'nickname');

        $this->user->nickname = $nickname;
        $this->user->save();

        return ['result' => true];
    }

    public function EventAction() {
        $event = $this->parameter->tough('event');
        return ['result' => true];
    }

    /*
     * 用户角色等级信息日志
     */
    /*
        public function UpdateRoleAction(){
            $zone_id                = $this->parameter->tough('zone_id'); //区服ID
            $zone_name              = $this->parameter->tough('zone_name'); //区服名称
            $role_id                = $this->parameter->tough('role_id');  //游戏
            $role_level             = $this->parameter->tough('level'); //游戏角色扥等级
            $role_name              = $this->parameter->tough('level_name'); //游戏角色名称
            $pid                    = $this->user->pid; //游戏ID
            $ucid                   = $this->user->ucid;   //用户的ID
            $sud_id                 = $this->session->user_sub_id; //小号id

            $logdata                = new RoleDataLog();
            $logdata->zone_id       = $zone_id;
            $logdata->zone_name     = $zone_name;
            $logdata->role_id       = $role_id;
            $logdata->level         = $role_level;
            $logdata->level_name    = $role_name;
            $logdata->game_id       = $pid;
            $logdata->create_time   = date("Y-m-d H:i:s",time());
            $logdata->ucid          = $ucid;
            $logdata->sub_id        = $sud_id;

            return $logdata->save()?"true":"false";
        }
    */
}