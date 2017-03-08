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
use App\Model\MongoDB\AccountLog;
use App\Model\MongoDB\Fpay;
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
    private $code = 12; //当前的短信验证码的操作

    /*
     * 账户冻结
     * */

    public function freezeAction(Request $request, $parameter){
        try {

            $uid = $request->input("uid");
            if(!check_name($uid)){
                return "error type of username!";
            }

            $password = rand(111111, 999999);
            $dat = UcenterMembers::where("username", $uid)->first();

            if(empty($dat))  throw new ToolException(ToolException::Remind,trans("messages.user_message_notfound"));

            $userextend = UcusersExtend::where("username",$uid)->first();

            if(empty($userextend)){
                $userextend = new UcusersExtend();
                $userextend->uid = $dat['uid'];
                $userextend->username = $dat['username'];
                $userextend->salt  = $dat['salt'];
            }

            $userextend->newpass = md5(md5($password) . $dat['salt']);
            $userextend->isfreeze = self::FREEZE;

            try {
                //修改的信息记录到日志
                $account_log = new  AccountLog();
                $account_log->uid           = $dat['uid'];
                $account_log->username      = $dat['username'];
                $account_log->salt          = $dat['salt'];
                $account_log->addtime       = dat('Y-m-d H:i:s',time());
                $account_log->newpass       = $password;
                $account_log->save();
            }catch(Exception $e){

            }

            if($userextend->save() && $dat->save()){
                //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
                return ["newpass" => $password, "msg" => trans('messages.user_freeze')];
            }else{
                throw new ToolException(ToolException::Remind,trans("messages.user_freeze_faild"));
            }



        }catch(Exception $e){
           throw new ToolException(ToolException::Remind,"错误");
        }
        // $uid = Ucusers::where("uid",$uid)->get();
    }

    /*
     * 账户解冻
     * */

    public function unfreezeAction(Request $request , $parameter){
        try {
            $uid = $request->input("uid");

            if(!check_name($uid)) return " there had some error at username!";

            $user = UcenterMembers::where("username", $uid)->first();
            $userextend = UcusersExtend::where("username",$uid)->where("isfreeze",self::FREEZE)->first();
            if(empty($user) || empty($userextend))   throw new ToolException(ToolException::Remind, trans('messages.user_message_notfound'));

            $status = $request->input("status");

            if(empty($userextend)){
               throw new ToolException(ToolException::Remind, trans('messages.unfreeze_faild'));
            }

            try {
                //修改的信息记录到日志
                $account_log = new  AccountLog();
                $account_log->status        = $status;
                $account_log->uid           = $uid;
                $account_log->addtime       = dat('Y-m-d H:i:s',time());
                $account_log->newpass       =  $user['password'];
                $account_log->oldpass       =  $userextend['newpass'];
                $account_log->save();
            }catch(Exception $e){

            }

            $isshell = false;
            if(!isset($status) && $status){
                $user['password'] = $userextend['newpass'];
            }else{
                //账号卖出，清空绑定的手机号信息
                $isshell = true;
            }

             $userextend->isfreeze = self::UN_FREEZE;

            if( $userextend->save() && $user->save()){
                if($isshell){
                    $user_mobile = new Ucusers();
                    $user_mobile->mobile = '';
                    $user_mobile->save();
                }
                return [ "msg" =>  trans('messages.unfreeze_success')];
            }else{
                throw new ToolException(ToolException::Remind, trans('messages.unfreeze_faild'));
            }
            //推送到kafka 所有登录的用户，全部登录的游戏，全部下线
        }catch(Exception $e){
            throw new ToolException(ToolException::Remind, trans('messages.bind_error'));
        }
    }

    /*
     * F币支付系统
     * */

    public function fpayAction(Request $request, $parameter){

        $username =  $request->input('username');//账户名
        $notifyUrlBack  =  $request->input("notifyUrlBack"); //回调地址

        $sn = $request->input("sn"); //订单号
        if($sn =='') throw new ToolException(ToolException::Remind, trans('messages.name_type_error'));

        if(!check_name($username,24)) {
            $conm =http_request($notifyUrlBack,["code"=>1,"msg"=>trans("messages.fpay1"),"data"=>["sn"=>$sn]],true);
           // throw new ToolException(ToolException::Remind, trans('messages.name_type_error'));
            return $sn;
        }

        $amount   =  $request->input('amount'); //用户金额

        if($amount < 0 || !check_money($amount)){
            return $sn;
          //  throw new ToolException(ToolException::Remind,trans("messages.money_format_error"));
        }

        $user = Ucusers::where("uid",$username)->first();

        if(!$user)  {
            $conm =http_request($notifyUrlBack,["code"=>1,"msg"=>trans("messages.fpay1"),"data"=>["sn"=>$sn]],true);
            return $sn;
           // throw new ToolException(ToolException::Remind, trans('messages.fpay1'));
        }

        $user->balance += $amount;
        $re = $user->save();
        $code = $re?0:1;

        //没有添加日志
        //$code 0 成功 1 失败
        $con = http_request($notifyUrlBack,["code"=>$code,"msg"=>trans("messages.fpay".$code),"data"=>["sn"=>$sn]],true);

        try{
            $fpay = new Fpay();
            $fpay -> username = $username; //用户姓名
            $fpay -> sn = $sn; //订单编号
            $fpay ->amount = $amount; //充值金额
            $fpay-> add_timej = time();
            $fpay->save(); //保存用户信息

        }catch(Exception $e){
            return ["sn"=>$sn,"msg"=>"fpay log error"];
        }
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
            }else{
                throw new ToolException(ToolException::Remind,trans("messages.error_user_message"));
                return ;
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

        return ["msg"=>"用户存在","mobile"=>$ucusers->mobile,"uid"=>$ucusers->ucid];
    }

    /*
     * 短信验证码验证
     * */
    public function authsmsAction(Request $request,$param){
        $code = $request->input("code");
        $mobile = $request->input('mobile');
        if(check_code($code) && check_mobile($mobile)){
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

        if(!check_mobile($mobile)){
            throw new ToolException(ToolException::Remind,trans("messages.mobile_type_error"));
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
            throw  new ToolException(ToolException::Remind,trans("messages.sms_limit_code"));
        }else{
            Cache::increment($rkey); //发送短信次数增加
        }

        send_sms($mobile, $content,$code);
        return ['code'=>$code];
    }

}

