<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;

class ResetPasswordController extends Controller
{
    public function RequestAction() {
        $token = $this->parameter->tough('token');
        $password = $this->parameter->tough('password', 'password');

        $token = decrypt3des($token);
        if(!$token) {
            throw new ApiException(ApiException::Error, trans('messages.reset_password_invalid_page'));
        }

        $token_info = json_decode($token, true);
        if(!$token_info) {
            throw new ApiException(ApiException::Error, trans('messages.reset_password_invalid_page'));
        }

        if(@$token_info['t'] < time()) {
            throw new ApiException(ApiException::Remind, trans('messages.reset_password_invalid_page'));
        }

        $user = Ucuser::from_cache($token_info['ucid']);
        if(!$user) {
            throw new ApiException(ApiException::Error, trans('messages.user_not_exists'));
        }

        $old_password = $user->password;
        $user->setPassword($password);
        $user->save();

        async_execute('expire_session', $user->ucid);
        user_log($user, $this->procedure, 'reset_password', '【重置用户密码】通过自助页面，旧密码[%s]，新密码[%s]', $old_password, $user->password);

        return ['result' => true];
    }
}