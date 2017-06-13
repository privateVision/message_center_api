<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;

use App\Model\Ucuser;

class GuestController extends Controller {

    use LoginAction;

    const Type = 1;

    public function getLoginUser() {
        $password = $this->parameter->get('password');
        $device_id = $this->parameter->tough('_device_id');

        $user = Ucuser::from_cache_device_uuid($device_id);
        if($user) {
            /*
            if($password) {
                $user->setPassword($password);
                $user->save();
            }
            */
            return $user;
        }

        $username = username();
        // XXX 兼容老的客户端传过来的密码
        if(!$password) {
            $password = rand(100000, 999999);
        }

        $user = self::baseRegisterUser([
            'uid' => $username,
            'password' => $password,
            'device_uuid' => $device_id,
        ]);

        user_log($user, $this->procedure, 'register', '【注册】通过“游客登录”注册，用户名(%s)，密码[%s]', $username, $user->password);

        return $user;
    }
}