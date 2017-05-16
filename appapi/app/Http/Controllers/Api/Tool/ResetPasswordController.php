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
            throw new ApiException(ApiException::Error, "修改失败，页面已失效"); // LANG:page_invalid
        }

        $token_info = json_decode($token, true);
        if(!$token_info) {
            throw new ApiException(ApiException::Error, "修改失败，页面已失效"); // LANG:page_invalid
        }

        if(@$token_info['t'] < time()) {
            throw new ApiException(ApiException::Remind, "修改失败，页面已失效"); // LANG:page_invalid
        }

        $user = Ucuser::from_cache($token_info['ucid']);
        if(!$user) {
            throw new ApiException(ApiException::Error, "用户不存在"); // LANG:user_not_exists
        }

        $old_password = $user->password;
        $user->setPassword($password);
        $user->save();

        async_execute('expire_session', $user->ucid);
        user_log($user, $this->procedure, 'reset_password', '【重置用户密码】通过自助页面，旧密码[%s]，新密码[%s]', $old_password, $user->password);

        return ['result' => true];
    }
}