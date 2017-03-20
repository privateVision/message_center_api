<?php
namespace App;

use App\Parameter;
use App\Redis;
use App\Model\Session;
use App\Model\UserProcedure;
use App\Model\UserProcedureExtra;
use App\Model\User;

class Event
{
	public static function onLoginAfter(User $user, $pid, $rid) {

        $user_procedure_extra = UserProcedureExtra::where('ucid', $user->ucid)->where('status', UserProcedureExtra::Status_Normal)->orderBy('priority', 'desc')->first();

        $user_procedure = null;
        if(!$user_procedure_extra) {
            $user_procedure = UserProcedure::tableSlice($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();
            if(!$user_procedure) {
                $user_procedure = UserProcedure::tableSlice($user->ucid);
                $user_procedure->ucid = $user->ucid;
                $user_procedure->pid = $pid;
                $user_procedure->rid = $rid;
                $user_procedure->old_rid = $rid;
                $user_procedure->cp_uid = $user->ucid;
                $user_procedure->name = auto_increment('table_user_procedure') . '01';
                $user_procedure->priority = time();
                $user_procedure->last_login_at = datetime();
                $user_procedure->save();

            } else {
                $user_procedure->priority = time();
                $user_procedure->last_login_at = datetime();
                $user_procedure->save();
            }
        }

        $session = new Session;
        $session->ucid = $user->ucid;
        $session->user_procedure_id = $user_procedure->id;
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();
         
        $user->uuid = $session->token; // todo: 兼容旧的自动登陆
        $user->last_login_at = datetime();
        //$user->default_user_procedure_id = $user_procedure->id;
        $user->save();

        return [
            'openid' => $user_procedure_extra ? $user_procedure_extra->cp_uid : $user_procedure->cp_uid,
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