<?php
namespace App\Listeners;

use App\Events\LoginEvent;
use App\Model\Session;

class LoginListener
{

    public function __construct()
    {

    }

    public function handle(LoginEvent $event)
    {
        $ucuser = $event->ucuser;

        $session = new Session;
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

        return [
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
        ];
    }
}