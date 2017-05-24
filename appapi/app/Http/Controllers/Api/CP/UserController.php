<?php
namespace App\Http\Controllers\Api\CP;

use App\Exceptions\ApiException;
use App\Session;

class UserController extends Controller{

    public function CheckAuthAction() {
        $token = $this->parameter->tough("token");
        $openid = $this->parameter->tough("open_id");
        $appid = $this->parameter->tough("app_id");

        //查询当前的session
        $session = Session::find($token);
        if(!$session || $session->cp_uid !== $openid || $session->pid !== $appid){
            return '__false';
        } else {
            return '__true';
        }
    }
}