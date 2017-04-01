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

        $user->setPassword($password);
        $user->save();

        return ['result' => true];
    }
}