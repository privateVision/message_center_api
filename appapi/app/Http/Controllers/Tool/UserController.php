<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/27
 * Time: 10:58
 */

namespace App\Http\Controllers\Tool;

use App\Event;
use App\Exceptions\ToolException;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Sms;
use App\Model\Ucusers;
use App\Model\UcusersExtend;
use App\Model\UcuserTotalPay;

use Illuminate\Support\Facades\Cache;
use Mockery\CountValidator\Exception;

use Symfony\Component\HttpFoundation\Request;
use App\Http\Controllers\Tool\Controller;

class UserController extends Controller{
    const UN_FREEZE = 0;
    const FREEZE = 1;
    const HAD_SHELL = 2;
    private $code = 12; //当前的短信验证码的操作

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
            $status = $userextend->save();

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
        $notifyUrlBack  =  $request->input("notifyUrlBack"); //回调地址

        $sn = $request->input("sn"); //订单号

        if(!check_name($username,24)) {
            $conm =http_request($notifyUrlBack,["code"=>1,"msg"=>trans("messages.fpay1"),"data"=>["sn"=>$sn]],true);
            throw new ToolException(ToolException::Remind, trans('messages.name_type_error'));
            return "error type user!";
        }


        $amount   =  $request->input('amount'); //用户金额

        if($amount < 0 || !preg_match("/^(\d+).?(?=\d+)(.\d{0,4})?$/",$amount)){

            throw new ToolException(ToolException::Remind,trans("messages.money_format_error"));
        }

        $user = Ucusers::where("uid",$username)->first();

        if(!$user)  {
            $conm =http_request($notifyUrlBack,["code"=>1,"msg"=>trans("messages.fpay1"),"data"=>["sn"=>$sn]],true);
            throw new ToolException(ToolException::Remind, trans('messages.fpay1'));
            return "no user";
        }

        $user->balance += $amount;
        $re = $user->save();
        $code = $re?0:1;

        //没有添加日志
        //$code 0 成功 1 失败
        $con = http_request($notifyUrlBack,["code"=>$code,"msg"=>trans("messages.fpay".$code),"data"=>["sn"=>$sn]],true);

        echo $con ;
    }


    /*
     * 验证当前的账号的信息
     * */

    public function authorizeAction(Request $request, $arguments = [])
    {
        $username = $request->input("username");
        if(!check_name($username)){
            throw new ToolException(ToolException::Remind,trans("messages.error_user_message"));
        }

        $password = $request->input("password");

        if($password =='' || strlen($password)>32){
            throw new ToolException(ToolException::Remind,trans("messages.password_type_error"));
        }

        $ucusers = Ucusers::where('uid', $username)->orWhere('mobile', $username)->get();

        if(count($ucusers) == 0)  { throw new ToolException(ToolException::Remind, trans("messages.error_user_message")); }

        foreach($ucusers as $v) {

            if($v->ucenter_members->checkPassword($password)) {
                $ucusers = $v;
            }
        }

        if( $ucusers->mobile ==''){
            throw new ToolException(ToolException::UNBIND_MOBILE, trans("messages.please_bind_mobile"));
        }

        // todo: 当前充值金额是从表ucuser_total_pay读取
        // 验证当前的充值金额
       $dat =  UcuserTotalPay::where("ucid",$ucusers->ucid)->first();

        if($dat['pay_fee'] < 1000 ) {
            throw new ToolException(ToolException::Remind,trans("messages.nomoney"));
            return ;
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
        if(preg_match($pater,$code) && preg_match("/^1[34578]\d{9}$/",$mobile)){
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

        if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
            throw new ToolException(ToolException::Remind,"messages.mobile_type_error");
        }

        $code = rand(111111,999999);
        $content = trans("messages.sms_code").$code;

        //发送短信验证码限制 防止短信炸弹
        $rkey = $mobile.":".$this->code;
        $numj = Cache::get($rkey);

        if(!$numj ) {
            Cache::store("redis")->put($rkey, 1, 60 * 24); //保存一天 一天内发送短信的次数的限制
        }
        if($numj == 3){
            throw  new ToolException(ToolException::Remind,"messages.sms_limit_code");
        }else{
            Cache::increment($rkey); //发送短信次数增加
        }

        send_sms($mobile, $content,$code);

        return ['code'=>$code];
    }

}

