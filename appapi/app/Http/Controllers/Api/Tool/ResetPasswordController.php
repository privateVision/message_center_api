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
            throw new ApiException(ApiException::Error, "token无效");
        }

        $token_info = json_decode($token, true);
        if(!$token_info) {
            throw new ApiException(ApiException::Error, "token无效");
        }

        if(@$token_info['t'] < time()) {
            throw new ApiException(ApiException::Error, "token已过期");
        }

        $user = Ucuser::from_cache($token_info['ucid']);
        if(!$user) {
            throw new ApiException(ApiException::Error, "用户不存在");
        }

        $old_password = $user->password;
        $user->setPassword($password);
        $user->save();

        async_execute('expire_session', $user->ucid);
        user_log($user, $this->procedure, 'reset_password', '【重置用户密码】通过自助页面，旧密码[%s]，新密码[%s]', $old_password, $user->password);

        return ['result' => true];
    }
}