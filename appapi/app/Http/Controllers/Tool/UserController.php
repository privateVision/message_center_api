<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/27
 * Time: 10:58
 */

namespace App\Http\Controllers\Tool;

use App\Exceptions\ToolException;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Ucusers;
use Mockery\CountValidator\Exception;
use Mockery\Generator\Parameter;
use Symfony\Component\HttpFoundation\Request;
use App\Http\Controllers\Tool\Controller;

class UserController extends Controller{

    /*
     * 账户冻结
     * */

    public function freezeAction(Request $request, $parameter){
        try {
            $uid = $request->input("username");
            $password = rand(111111, 999999);
            $user = UcenterMembers::where("username", $uid)->first();

            $dat = $user->toArray();
            $user->newpass = md5(md5($password) . $dat['salt']);
            $user->isfreeze = 1;
            $user->save();

            //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
            return ['data' => ["newpass" => $password], "msg" => trans_choice('message.user_freeze'), "code" => 0];
        }catch(Exception $e){
            new ToolException(ToolException::Remind,"错误");
        }
        // $uid = Ucusers::where("uid",$uid)->get();
    }

    /*
     * 账户解冻
     * */

    public function unfreezeAction(Request $request , $parameter){
        try {
            $uid = $request->input("username");
            $user = UcenterMembers::where("username", $uid)->first();
            $dat = $user->toArray();
            $user->isfreeze = 0;
            return $user->save()?['data' =>[] , "msg" =>  trans_choice('messages.unfreeze_success'), "code" => 0]:['data' =>[] , "msg" =>  trans('messages.unfreeze_faild'), "code" => 1];
            //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
        }catch(Exception $e){
            new ToolException(ToolException::Remind, trans_choice('messages.bind_error'));
        }
    }

    /*
     * F币支付系统
     * */

    public function fpayAction(Request $request, $parameter){

        $username =  $request->input('_username');//账户名

        $partername  = "/(^\d+(?=\w+)[a-zA-Z]+\w+$)|(^[a-zA-Z]+(?=\d+)\d+\w+$)/"; //正则匹配

        if(preg_match($partername,$username))  new ToolException(ToolException::Remind, trans('messages.name_type_error'));
        $amount   =  $request->input('_amount'); //用户金额
        $notifyurl  =  $request->input("_notifyurl"); //回调地址

        $sn = $request->input("_sn"); //订单号

        $user = Ucusers::where("uid","afMTQwNzkwYmEz")->first();
        $user->balance += $amount;
        $re = $user->save();
        $code = $re?0:1;
        //$code 0 成功 1 失败
        return http_request($notifyurl,["code"=>0,"msg"=>trans("".$code),"data"=>["sn"=>$sn]],true);

    }

}