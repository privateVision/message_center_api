<?php
namespace App\Http\Controllers\Api\Account;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller as BaseController;

use App\Model\Ucuser;
use App\Parameter;

abstract class Controller extends BaseController {

    /**
     * @param $data
     * @param $data['uid']
     * @param $data['password']
     * @param $data['email']   可选
     * @param $data['nickname']  可选
     * @param $data['mobile']   可选
     * @param $data['salt']  可选
     * @param $data['device_uuid'] 可选
     */
    protected function baseRegisterUser($data) {

        $user = new Ucuser;
        $user->uid = $data['uid'];
        $user->email = (isset($data['email']) && !empty($data['email'])) ? $data['email'] : $data['uid'].'@anfan.com';
        $user->setPassword($data['password']);
        $user->nickname = (isset($data['nickname']) && !empty($data['nickname'])) ? $data['nickname'] : '暂无昵称';

        if(isset($data['mobile']) && !empty($data['mobile'])) {
            $user->mobile = $data['mobile'];
        }
        if(isset($data['salt']) && !empty($data['salt'])) {
            $user->salt = $data['salt'];
        }
        if(isset($data['device_uuid']) && !empty($data['device_uuid'])) {
            $user->device_uuid = $data['device_uuid'];
        }
        $user->regtype = static::Type;
        $user->regip = getClientIp();
        $user->rid = $this->parameter->tough('_rid');
        $user->pid = $this->procedure->pid;
        $user->regdate = time();
        $user->imei = $this->parameter->get('_imei', '');
        $user->device_id= $this->parameter->get('_device_id', '');

        $user->save();

        user_log($user, $user->procedure, 'register', '【注册】通过“方式'.$user->regtype.'”注册，用户名(%s)，密码[%s]', $data['uid'], $user->password);

        return $user;
    }
}