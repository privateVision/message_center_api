<?php
namespace App;

use App\Model\Session;

class Event
{
	public static function onLoginAfter($user) {
        $session = new Session;
        $session->ucid = $user->ucid;
        $session->is_service_login = $user->isFreeze();
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();

        // todo: 兼容旧的自动登陆
        $user->uuid = $session->token;
        $user->save();

        $retailer = $user->retailers;

        return [
            'uid' => $user->ucid,
            'username' => $user->uid,
            'mobile' => $user->mobile,
            'avatar' => env('AVATAR'),
            'is_real' => $user->isReal() ? 1 : 0,
            'is_adult' => $user->isAdult() ? 1 : 0,
            'rtype' => $retailer ? $retailer->rtype : 0,
            'vip' => $user->vip(),
            'token' => $session->token,
            'balance' => $user->balance,
        ];
	}

	public static function onLogoutAfter($user) {

	}

	public static function onRegisterAfter($user) {
		return static::onLoginAfter($user);
	}
}
