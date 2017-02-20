<?php
namespace App;

class Event
{
	public static onLogin(&$user, &$session) {
		$session->uid = $user->uid;
		$session->save();
	}

	public static onLogout(&$user, &$session) {
		$session->expired_ts = time();
		$session->save();
	}

	public static onRegister(&$user, &$session) {
		$session->uid = $user->uid;
		$session->save();
	}
}