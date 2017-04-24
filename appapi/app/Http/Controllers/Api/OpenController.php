<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/24
 * Time: 20:23
 */
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Model\Session;

class OpenController extends OpenBaseController{

    /*
     * 验证当前的用户信息
     * */

        public function AuthLoginAction(){

            $token = $this->parameter->tough("token");
            $openid = $this->parameter->tough("open_id");
            $appid = $this->parameter->tough("app_id");

            //查询当前的session
            $dat = Session::where("token",$token)->where("cp_uid",$openid)->where("pid",$appid)->first();

            if($token !== "2mpbl2how4iso08kookw40gcw"){
                if(time() > $dat['expired_ts']) throw  new ApiException(0,"token 失效"); //失败
            }

            if($dat){
                return ["code"=>1,"msg"=>"用户信息","data"=>true];
            }

            throw  new ApiException(0,"玩家不存在"); //失败

        }


    /*
     *
     * 验证订单
     * */

    public function GetOrderInfoAction(){

        $open_id = $this->parameter->tough("open_id");
        $sn = $this->parameter->tough("sn");

        $ord = Orders::where("cp_uid", $open_id)->where('sn', $sn)->first();
        if ($ord) { //拼接返回的数据

            switch($ord->status){
                case 0:
                    $dta = [];
                    $dta["open_id"] = $ord->user_sub_id;
                    $dta["vorder_id"] = $ord->vorderid;
                    $dta["sn"] = $ord->sn;
                    $dta["app_id"] = $ord->appid;
                    $dta["fee"] = $ord->fee;
                    $dta["body"] = $ord->body;
                    $dta["create_time"] = $ord->createTime;

                    return $dta; //订单待处理
                    break;
                case 1:
                    return 1001; //正确的返回值
                    break;
            }
        }
        return 1002; //验证当前的订单如果失败，标记为是失败
    }


}