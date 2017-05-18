<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use App\Parameter;
use App\Session;
use App\Model\Ucuser;

class TokenController extends Controller {

    use LoginAction;

    const Type = 5;

    public function getLoginUser() {
        $token = $this->parameter->tough('_token');

        $session = Session::find($token);
        if(!$session) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_token'));
        }

        // todo: 验证token有效期

        if(!$session->ucid) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_token'));
        }

        $user = Ucuser::from_cache($session->ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_token'));
        }

        return $user;
    }
    
    public function getDefaultUserSubId(Ucuser $user) {
        $user_sub_id = $this->parameter->get('user_sub_id');
        return $user_sub_id ?: null;
    }
}