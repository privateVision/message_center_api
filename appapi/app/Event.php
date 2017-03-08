<?php
namespace App;

use App\Exceptions\ApiException;

class Event
{
	public static function onLogin(&$ucuser, &$session) {
		$session->ucid = $ucuser->ucid;
        $session->is_service_login = $ucuser->isFreeze();
		$session->save();

        // todo: 兼容旧的自动登陆
		$ucuser->uuid = $session->access_token;
		$ucuser->save();

		$retailer = $ucuser->retailers;
        return array (
            'uid' => $ucuser->ucid,
            'username' => $ucuser->uid,
            'mobile' => $ucuser->mobile,
            'avatar' => env('AVATAR'),
            'is_real' => $ucuser->isReal() ? 1 : 0,
            'is_adult' => $ucuser->isAdult() ? 1 : 0,
            'rtype' => $retailer ? $retailer->rtype : 0,
            'vip' => $ucuser->vip(),
            'token' => $ucuser->uuid,
            'balance' => $ucuser->balance,
        );
	}

	public static function onLogout(&$ucuser, &$session) {

	}

	public static function onRegister(&$ucuser, &$session) {
        return static::onLogin($ucuser, $session);
	}
}