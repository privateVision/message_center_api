<?php
/*
* @Author: anchen
* @Date:   2017-02-17 18:28:02
* @Last Modified by:   anchen
* @Last Modified time: 2017-02-18 10:55:11
*/

namespace App\Http\Controllers\Api;
use App\Model;
use App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;
use App\Model\Gamebbs56\UcenterMembers;

/**
*
*/
class UserController extends BaseController
{
    public function userRegisterAction(){
        $salt = mt_rand(111111,999999);
    }


    public function quicknameAction(){
      do{
        $username = "af".mt_rand(11111111,99999999);
        $dt = UcenterMembers::where("username",$username)->get();
        if(count($dt) == 0){
          return $username;
          exit;
        }
      }while (true);
    }

}
