<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\UcusersUUID;

class GuestController extends Controller {

    use LoginAction;

    const Type = 1;

    public function getLoginUser() {
        $imei = $this->parameter->get('_imei', '');
        $password = $this->parameter->get('password');
        $device_id = $this->parameter->tough('_device_id');

        $user = Ucuser::from_cache_device_uuid($device_id);
        if($user) {
            return $user;
        }

        $username = username();

        // XXX 兼容老的客户端是传过来的密码
        if(!$password) {
            $password = rand(100000, 999999);
        }
        
//        $user = new Ucuser;
//        $user->uid = $username;
//        $user->email = $username . "@anfan.com";
//        $user->nickname = '暂无昵称';
//        $user->setPassword($password);
//        $user->regtype = static::Type;
//        $user->regip = getClientIp();
//        $user->rid = $this->parameter->tough('_rid');
//        $user->pid = $this->procedure->pid;
//        $user->regdate = time();
//        $user->device_uuid = $device_id;
//        $user->imei = $imei;
//        $user->device_id= $device_id;
//        $user->save();
//
//        user_log($user, $this->procedure, 'register', '【注册】通过“游客登录”注册，用户名(%s)，密码[%s]', $username, $user->password);
        $udt = array(
            'uid'=>$username,
            'password'=>$password,
            'device_uuid'=>$device_id
        );
        //游客注册账号
        $user = self::baseRegisterUser($udt);

        return $user;
    }
}