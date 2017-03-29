<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\User;
use App\Model\Session;

class TokenController extends Controller {

    use LoginAction;

    public function getLoginUser(Request $request, Parameter $parameter) {
        $token = $parameter->tough('_token');

        $session = Session::where('token', $token)->first();
        if(!$session) {
            throw new ApiException(ApiException::Remind, '会话已结束，请重新登录');
        }

        // todo: 验证token失效

        if(!$session->ucid) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        $user = User::from_cache($session->ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        return $user;
    }
    
    public function getDefaultUserSubId(User $user, Request $request, Parameter $parameter) {
        $user_sub_id = $parameter->get('user_sub_id');
        return $user_sub_id ?: null;
    }
}