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
/**
*
*/
class UserController extends BaseController
{
    public function userRegisterAction(){
        $username = "af".mt_rand(11111111,99999999);
        $sql = "select uid from 56gamebbs.pre_ucenter_members limit 1,10";
        $dt = DB::select($sql);
        if(count($dt));
        exit ;
        $dat =  app('db')->select($sql);
        var_dump($dat);
        return "赴京的是吉恩管的发展史";
    }


    public function quicknameAction(){

    }
}