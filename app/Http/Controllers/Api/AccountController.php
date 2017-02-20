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
        $access_token = $parameter->tough('token');

        $session = Session::where('access_token', $access_token)->first();
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

        return Event::onLogin($ucuser, $this->session);
    }
}