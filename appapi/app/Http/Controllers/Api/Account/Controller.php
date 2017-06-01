<?php
namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Api\Controller as BaseController;
use App\Model\Ucuser;

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
        $user->email = @$data['email'] ?: $data['uid'].'@anfan.com';
        $user->nickname = @$data['nickname'] ?: '暂无昵称';

        if(isset($data['mobile'])) {
            $user->mobile = $data['mobile'];
        }

        if(!empty(@$data['salt']) && !empty(@$data['password'])) {
            $user->salt = $data['salt'];
            $user->password = $data['password'];
        } else {
            $user->setPassword($data['password']);
        }

        if(!empty(@$data['device_uuid'])) {
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

        return $user;
    }
}