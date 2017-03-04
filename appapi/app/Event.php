<?php
namespace App;

use App\Exceptions\ApiException;

class Event
{
	public static function onLogin(&$ucuser, &$session) {
        if($session->ucid) {
            //throw new ApiException(ApiException::Error, 'access_token已被使用，请重新启动游戏获取');
        }

        if($ucuser->isFreeze()) {
            throw new ApiException(ApiException::Remind, '帐号已被冻结，无法登陆');
        }

		$session->ucid = $ucuser->ucid;
		$session->save();

		$ucuser->uuid = $session->access_token;
		$ucuser->save();

		$retailer = $ucuser->retailers;

        return array (
            'uid' => $ucuser->ucid,
            'username' => $ucuser->uid,
            'mobile' => $ucuser->mobile,
            'avatar' => env('AVATAR'),
            'is_real' => $ucuser->is_rela(),
            'is_adult' => $ucuser->is_adult(),
            'rtype' => $retailer ? $retailer->rtype : 0,
            'vip' => $ucuser->vip(),
            'token' => $ucuser->uuid,
        );
	}

	public static function onLogout(&$ucuser, &$session) {
		$session->expired_ts = time();
		$session->save();
	}

	public static function onRegister(&$ucuser, &$session) {
        return static::onLogin($ucuser, $session);
	}
}