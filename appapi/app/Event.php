<?php
namespace App;

use App\Parameter;
use App\Redis;
use App\Model\Session;
use App\Model\UserSub;
use App\Model\UserSubService;
use App\Model\User;
use Request;

class Event
{
    public static function onLoginAfter(User $user, $pid, $rid, $user_sub = null) {

        $user_sub_service = null;

        if(!$user_sub) {

            $user_sub_service = UserSubService::where('ucid', $user->ucid)->where('pid', $pid)->where('status', UserSubService::Status_Normal)->orderBy('priority', 'desc')->first();

            if($user_sub_service) {
                $user_sub = UserSub::tableSlice($user->src_ucid)->from_cache($user_sub_service->user_sub_id);
            }

            if(!$user_sub) {
                $user_sub = UserSub::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();
            }

            if(!$user_sub) {
                $user_sub = UserSub::tableSlice($user->ucid);
                $user_sub->id = uuid($user->ucid);
                $user_sub->ucid = $user->ucid;
                $user_sub->pid = $pid;
                $user_sub->rid = $rid;
                $user_sub->old_rid = $rid;
                $user_sub->cp_uid = $user->ucid;
                $user_sub->name = '小号01';
                $user_sub->priority = time();
                $user_sub->last_login_at = datetime();
                $user_sub->save();
            }
        }

        if(!$user_sub_service) {
            $user_sub->priority = time();
            $user_sub->last_login_at = datetime();
            $user_sub->save();
        }
        

        $session = new Session;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->cp_uid = $user_sub->cp_uid;
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->saveAndCache();
        
        $user->uuid = $session->token; // todo: 兼容旧的自动登陆
        $user->last_login_at = datetime();
        $user->save();

        return [
            'openid' => strval($user_sub->cp_uid),
            'sub_nickname' => strval($user_sub->name),
            'uid' => $user->ucid,
            'username' => $user->uid,
            'mobile' => strval($user->mobile),
            'avatar' => $user->avatar ? $user->avatar : env('AVATAR'),
            'is_real' => $user->isReal(),
            'is_adult' => $user->isAdult(),
            'vip' => $user->vip,
            'token' => $session->token,
            'balance' => $user->balance,
        ];
    }

    public static function onLogoutAfter(User $user) {

    }

    public static function onRegisterAfter(User $user, $pid, $rid) {
        $user->regdate = time();
        $user->date = date('Ymd');
        $user->delaySave();
        
        return static::onLoginAfter($user, $pid, $rid);
    }

    public static function onResetPassword(User $user, $new_password) {
        $user->password = $new_password;
        $user->save();
        return $user;
    }
}