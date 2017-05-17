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
        $password = $this->parameter->get('password');
        $uuid = $this->parameter->tough('_device_id');

        $user = Ucuser::from_cache_device_uuid($uuid);
        if($user) {
            return $user;
        }

        $username = username();

        // todo: 兼容老的客户端是传过来的密码
        if(!$password) {
            $password = rand(100000, 999999);
        }
        
        $user = new Ucuser;
        $user->uid = $username;
        $user->email = $username . "@anfan.com";
        $user->nickname = '暂无昵称';
        $user->setPassword($password);
        $user->regtype = static::Type;
        $user->regip = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->parameter->tough('_appid');
        $user->regdate = time();
        $user->device_uuid = $uuid;
        $user->save();
        
        $imei = $this->parameter->get('_imei', '');
        $device_id = $this->parameter->get('_device_id', '');
        if($imei || $device_id) {
            $ucusers_uuid =  new UcusersUUID();
            $ucusers_uuid->ucid = $user->ucid;
            $ucusers_uuid->imei = $imei;
            $ucusers_uuid->device_id= $device_id;
            $ucusers_uuid->asyncSave();
        }

        user_log($user, $this->procedure, 'register', '【注册】通过“游客登录”注册，用户名(%s)，密码[%s]', $username, $user->password);

        return $user;
    }
}