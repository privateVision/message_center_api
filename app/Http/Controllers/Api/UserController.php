<?php
/*
* @Author: anchen
* @Date:   2017-02-17 18:28:02
* @Last Modified by:   anchen
* @Last Modified time: 2017-02-18 10:55:11
*/

namespace App\Http\Controllers\Api;
use App\Exceptions\ApiException;
use App\Model;
use App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Ucusers;
use Illuminate\Http\Request;
use App\Parameter;

class UserController extends BaseController
{
    public function userRegisterAction(Request $request, Parameter $parameter){
      $salt       = mt_rand(111111,999999);
      $username   = $parameter->tough('username') ;
      $password   = $parameter->tough('password') ;
      $product    = $this->procedure->pid;
      $ird        = $this->session->rid;
      //$ip         = $parameter->touch('username');
      $ip         = '';
      $imei       = $this->session->imei;
      $token      = $this->session->access_token;
      //$is_mobile  = checkMobile($mo);
      $isTruename = checkName($username);
      $mobile = '1';
     // if(!$is_mobile) throw new ApiException(ApiException::Error, "手机号不是正确格式");
      if(!$isTruename || mb_strlen($username) >24 ) throw new ApiException(ApiException::Remind, "用户名格式不正确");

      $countNum = UcenterMembers::where("username",$username)->orWhere("username",$mobile)->count();

      $mcountr  = Ucusers::where("mobile",$username)->orWhere('mobile',$mobile)->count();

      if($countNum || $mcountr ){
          throw new  ApiException(ApiException::Remind, "您好，你已经注册过！");
      }

      $saltPass = getTypePass($password,$salt);
      $email = $username."@anfan.com";
      $ucenter = new UcenterMembers;
      $ucenter->password = $saltPass;
      $ucenter->email    = $email;
      $ucenter->salt     = $salt;
      $ucenter->regip    = $ip ?? $_SERVER["REMOTE_ADDR"];
      $ucenter->username = $username;
      $ucenter->regdate  = time();
      $ucenter->save();
      $ucid = $ucenter->uid;

      $ucuser = new Ucusers();
      $ucuser->ucid      = $ucid;
      $ucuser->uid  = $username;
      $ucuser->uuid      = $token;
      $ucuser->rid       = $ird;
      $ucuser->pid       = $product;
      $ucuser->createTime = date("Y-m-d H:i:s",time());
      $ucuser->save();

      $backdata = [
        "id" =>$ucid,
        "username" => $username,
        "password" => $password,
        "token"    => $token,
        "mobile"   =>  '',
        "avatar"   =>  'http://api5.zhuayou.com/avatar.png',
        "realname" => 0
      ];
      //注册成功返回数据

      return $backdata;
    }
    /*
     * 快速生成用户名
     * */
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

  /*
   * 用户冻结的操作
   * */
  public function freezeAction(Request $request){
    $param   = $request->get('param');
    $pdata   = decrypt3des($param);
    $uid     = $pdata['uid'];
    $newpass = $pdata['password'];

    Ucusers::where("ucid",$uid)->update();
  }

}
