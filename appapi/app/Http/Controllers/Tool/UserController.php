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
use App\Model\Orders;
use App\Model\Sms;
use App\Model\Ucusers;
use App\Model\UcusersExtend;
use App\Model\UcuserTotalPay;
use Mockery\CountValidator\Exception;
use Mockery\Generator\Parameter;
use Symfony\Component\HttpFoundation\Request;
use App\Http\Controllers\Tool\Controller;

class UserController extends Controller{
    const UN_FREEZE = 0;
    const FREEZE = 1;
    const HAD_SHELL = 2;
    /*
     * 账户冻结
     * */

    public function freezeAction(Request $request, $parameter){
        try {

            $uid = $request->input("uid");
            $password = rand(111111, 999999);
            $user = UcenterMembers::where("username", $uid)->first();
            $dat = $user->toArray();
            $userextend = UcusersExtend::where("username",$uid)->first();
            if(!$userextend){
                $userextend = new UcusersExtend();
                $userextend->uid = $dat['uid'];
                $userextend->username = $dat['username'];
                $userextend->salt  = $dat['salt'];
            }
            $userextend->newpass = md5(md5($password) . $dat['salt']);
            $userextend->isfreeze = 1;
            $userextend->save();
            //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
            return ['data' => ["newpass" => $password], "msg" => trans('message.user_freeze'), "code" => 0];

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
            $uid = $request->input("uid");
            $user = UcenterMembers::where("username", $uid)->first();
            $userextend = UcusersExtend::where("username",$uid)->where("isfreeze",self::FREEZE)->first();
            $status = $request->input("status");

            if(!$userextend){
                new ToolException(ToolException::Remind, trans('messages.unfreeze_faild'));
            }

            if($status){
                $user->password = $user->newpass;
            }else{
                $pass = $user->password;
                $userextend->isfreeze = self::UN_FREEZE;
                $userextend->newpass  = $user->$pass; //密码替换
                $userextend->isfreeze = self::HAD_SHELL;
                //$userextend->newpass  =
            }

            if( $userextend->save() && $user->save()){
                return [ "msg" =>  trans('messages.unfreeze_success')];
            }else{
                new ToolException(ToolException::Remind, trans('messages.unfreeze_faild'));
            }
            //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
        }catch(Exception $e){
            new ToolException(ToolException::Remind, trans('messages.bind_error'));
        }
    }

    /*
     * F币支付系统
     * */

    public function fpayAction(Request $request, $parameter){

        $username =  $request->input('username');//账户名

        $partername  = "/(^\d+(?=\w+)[a-zA-Z]+\w+$)|(^[a-zA-Z]+(?=\d+)\d+\w+$)/"; //正则匹配

        if(preg_match($partername,$username))  new ToolException(ToolException::Remind, trans('messages.name_type_error'));
        $amount   =  $request->input('amount'); //用户金额

        $notifyUrlBack  =  $request->input("notifyUrlBack"); //回调地址

        $sn = $request->input("sn"); //订单号

        $user = Ucusers::where("uid",$username)->first();

        if(!$user)  new ToolException(ToolException::Remind, trans('messages.fpay1'));
        $user->balance += $amount;
        $re = $user->save();
        $code = $re?0:1;
        //没有添加日志
        //$code 0 成功 1 失败
        if($code == 1)  new ToolException(ToolException::Remind, trans('messages.fpay1'));

        return  http_request($notifyUrlBack,["code"=>0,"msg"=>trans("messages.fpay".$code),"data"=>["sn"=>$sn]],true);
    }


    /*
     * 验证当前的账号的信息
     * */

    public function authorizeAction(Request $request, $arguments = [])
    {
        $username = $request->input("username");
        $password = $request->input("password");

        $ucusers = Ucusers::where('uid', $username)->orWhere('mobile', $username)->get();

        if(count($ucusers) == 0)  { throw new ToolException(ToolException::Remind, trans("messages.error_user_message"));}

        foreach($ucusers as $v) {

            if($v->ucenter_members->checkPassword($password)) {
                $ucusers = $v;
            }
        }

        if( $ucusers->mobile ==''){
            throw new ToolException(ToolException::UNBIND_MOBILE, trans("messages.please_bind_mobile"));
        }

        //验证当前的充值金额
       $dat =  UcuserTotalPay::were("uid",$ucusers->uid)->first();

        if($dat['pay_fee'] < 1 ) {
            throw new ToolException(ToolException::Remind,trans("messages.nomoney"));
        }

        return ["msg"=>"用户存在","mobile"=>$ucusers->mobile];
    }

    /*
     * 短信验证码验证
     * */
    public function authsmsAction(Request $request,$param){
        $code = $request->input("code");
        $mobile = $request->input('mobile');
        $pater = "/^\d{6}$/";
        if(preg_match($pater,$code)){
            $ms = Sms::where("mobile",$mobile)->orderBy('id', 'desc')->first();
            $time = time() - strtotime($ms['sendTime']);
            //验证码有效时间三十分钟
            if($ms['acode'] == $code && $time <= 1800 ){
                return ["msg"=>trans("messages.sms_code_success"),"uid"=>$ms->ucusers->uid];
            }else{
                throw new ToolException(ToolException::Remind,trans("messages.sms_code_error"));
            }
        }
    }

    public function sendmsAction(Request $request,$param){
        $mobile = $request->input("mobile");
        $code = rand(111111,999999);
        $content = trans("messages.sms_code").$code;
        send_sms($mobile, $content,$code);
        return ['code'=>$code];
    }

}

