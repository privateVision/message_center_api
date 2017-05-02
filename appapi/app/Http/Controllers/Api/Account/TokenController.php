<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Redis;
use App\Model\Ucuser;
use App\Model\Session;

class TokenController extends Controller {

    use LoginAction;

    public function getLoginUser() {
        $token = $this->parameter->tough('_token');

        $session = Session::from_cache_token($token);
        if(!$session) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        // todo: 验证token有效期

        if(!$session->ucid) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        $user = Ucuser::from_cache($session->ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '会话失效，请重新登录');
        }

        return $user;
    }
    
    public function getDefaultUserSubId(Ucuser $user) {
        $user_sub_id = $this->parameter->get('user_sub_id');
        return $user_sub_id ?: null;
    }
}