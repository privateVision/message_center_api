<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\User;

class GuestController extends Controller {

    use LoginAction;

    public function getLoginUser(Request $request, Parameter $parameter) {
        $uuid = $parameter->tough('_device_id');

        $user = User::from_cache_device_uuid($uuid);
        if($user) {
            return $user;
        }

        $username = username();
        $password = rand(100000, 999999);
        
        $user = new User;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = $username;
        $user->password = $password;
        $user->regip = $request->ip();
        $user->rid = $parameter->tough('_rid');
        $user->pid = $parameter->tough('_appid');
        $user->regdate = date('Ymd');
        $user->date = date('Ymd');
        $user->device_uuid = $uuid;
        $user->save();

        user_log($user, $this->procedure, 'register', '【注册】通过“游客登陆”注册，用户名(%s)，密码[%s]', $username, $user->password);

        return $user;
    }
}