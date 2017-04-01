<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;

class GuestController extends Controller {

    use LoginAction;

    public function getLoginUser() {
        $uuid = $this->parameter->tough('_device_id');

        $user = Ucuser::from_cache_device_uuid($uuid);
        if($user) {
            return $user;
        }

        $username = username();
        $password = rand(100000, 999999);
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = $username;
        $user->setPassword($password);
        $user->regip = $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->device_uuid = $uuid;
        $user->save();

        user_log($user, $this->procedure, 'register', '【注册】通过“游客登陆”注册，用户名(%s)，密码[%s]', $username, $user->password);

        return $user;
    }
}