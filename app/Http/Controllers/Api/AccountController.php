<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Event;
use App\Model\Session;
use App\Model\Ucusers;

class AccountController extends BaseController {

    protected $session = null;

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        $old_token = $parameter->tough('old_token');

        $session = Session::where('access_token', $old_token)->first();
        if(!$session || !$session->ucid) {
            throw new ApiException(ApiException::Remind, '登陆失败，请重新登陆');
        }

        if($session->expired_ts < time()) {
            throw new ApiException(ApiException::Remind, '登陆失败，会话已经过期，请重新登陆');
        }

        $ucuser = Ucusers::find($session->ucid);
        if(!$ucuser) {
            throw new ApiException(ApiException::Remind, '用户不存在，请重新登陆');
        }

        Event::onLogin($ucuser, $this->session);

        return array(
            'uid' => $ucuser->ucid,
            'username' => $ucuser->uid,
            'mobile' => $ucuser->mobile,
            'avatar' => '',
            'realname' => '',
            'rtype' => '',
            'vip' => '',
        );
    }
}