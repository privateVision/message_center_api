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
      $username = "af".mt_rand(11111111,99999999);
      //$count = $this->where("username",$username)->find();
      $dt = UcenterMembers::where("username",$username)->get();
      var_dump($dt);
      return $count;
        return "赴京的是吉恩管的发展史";
    }


    public function quicknameAction(){

    }






}
