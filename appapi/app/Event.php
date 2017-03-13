<?php
namespace App;

use App\Exceptions\ApiException;

class Event
{
	public static function onLogin(&$ucuser) {
		$session = new \App\Model\Session;
        $session->ucid = $ucuser->ucid;
        $session->is_service_login = $ucuser->isFreeze();
        $session->token = uuid();
        $session->expired_ts = time() + 2592000; // 1个月有效期
        $session->date = date('Ymd');
        $session->save();

        // todo: 兼容旧的自动登陆
		$ucuser->uuid = $session->token;
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
            'token' => $session->token,
            'balance' => $ucuser->balance,
        );
	}

	public static function onLogout(&$ucuser) {

	}

	public static function onRegister(&$ucuser) {
        return static::onLogin($ucuser);
	}
}