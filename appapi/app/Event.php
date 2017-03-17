<?php
namespace App;

use App\Parameter;
use App\Model\Session;
use App\Model\UcuserProcedure;
use App\Model\UcuserProcedureExtra;
use App\Model\User;

class Event
{
	public static function onLoginAfter(User $user, $pid, $rid) {

        $ucuser_procedure_extra = UcuserProcedureExtra::where('ucid', $user->ucid)->where('status', UcuserProcedureExtra::Status_Normal)->orderBy('priority', 'desc')->first();

        $ucuser_procedure = null;
        if(!$ucuser_procedure_extra) {
            $ucuser_procedure = UcuserProcedure::part($user->ucid)->where('ucid', $user->ucid)->where('pid', $pid)->where('is_freeze', false)->orderBy('priority', 'desc')->first();
            if(!$ucuser_procedure) {
                $ucuser_procedure = UcuserProcedure::part($user->ucid);
                $ucuser_procedure->ucid = $user->ucid;
                $ucuser_procedure->pid = $pid;
                $ucuser_procedure->rid = $rid;
                $ucuser_procedure->old_rid = $rid;
                $ucuser_procedure->cp_uid = $user->ucid;
                $ucuser_procedure->priority = time();
                $ucuser_procedure->last_login_at = date('Y-m-d H:i:s');
                $ucuser_procedure->save();
            } else {
                $ucuser_procedure->priority = time();
                $ucuser_procedure->last_login_at = date('Y-m-d H:i:s');
                $ucuser_procedure->save();
            }
        }

        $session = new Session;
        $session->ucid = $user->ucid;
        $session->ucuser_procedure_id = $ucuser_procedure->id;
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();

        // todo: 兼容旧的自动登陆
        $user->uuid = $session->token;
        $user->save(); // 这一句必须要

        return [
            'openid' => $ucuser_procedure_extra ? $ucuser_procedure_extra->cp_uid : $ucuser_procedure->cp_uid,
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
