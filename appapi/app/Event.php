<?php
namespace App;

use App\Parameter;
use App\Redis;
use App\Model\Session;
use App\Model\UserSub;
use App\Model\UserSubService;
use App\Model\User;

class Event
{
    public static function onLoginAfter(User $user, $pid, $rid) {

        $user_sub_service = UserSubService::where('ucid', $user->ucid)
            ->where('pid', $pid)
            ->where('status', UserSubService::Status_Normal)
            ->orderBy('priority', 'desc')
            ->first();


        $user_sub = null;
        if(!$user_sub_service) {

            $user_sub = UserSub::tableSlice($user->ucid)
                ->where('ucid', $user->ucid)
                ->where('pid', $pid)
                ->where('is_freeze', false)
                ->orderBy('priority', 'desc')
                ->first();

            if(!$user_sub) {
                $user_sub = UserSub::tableSlice($user->ucid);
                $user_sub->ucid = $user->ucid;
                $user_sub->pid = $pid;
                $user_sub->rid = $rid;
                $user_sub->old_rid = $rid;
                $user_sub->cp_uid = $user->ucid;
                $user_sub->name = base_convert(sprintf("%011d%09d", $user->ucid, $pid), 10, 36) . '01';
                $user_sub->priority = time();
                $user_sub->last_login_at = datetime();
                $user_sub->save();
            } else {
                $user_sub->priority = time();
                $user_sub->last_login_at = datetime();
                $user_sub->save();
            }
        }

        $session = new Session;
        $session->ucid = $user->ucid;
        $session->user_sub_id = $user_sub->id;
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();
         
        $user->uuid = $session->token; // todo: 兼容旧的自动登陆
        $user->last_login_at = datetime();
        $user->save();

        return [
            'openid' => strval($user_sub_service ? $user_sub_service->cp_uid : $user_sub->cp_uid),
            'uid' => $user->ucid,
            'username' => $user->uid,
            'mobile' => strval($user->mobile),
            'avatar' => $user->avatar ? $user->avatar : env('AVATAR'),
            'is_real' => $user->isReal(),
            'is_adult' => $user->isAdult(),
            'vip' => $user->vip(),
            'token' => $session->token,
            'balance' => $user->balance,
        ];
    }

    public static function onLogoutAfter(User $user) {

    }

    public static function onRegisterAfter(User $user, $pid, $rid) {
        return static::onLoginAfter($user, $pid, $rid);
    }
}