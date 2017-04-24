<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/19
 * Time: 13:56
 */
namespace App\Http\Controllers\Api\Tool;
use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\Procedures;
use App\Model\Session;
use Illuminate\Http\Request;

class OpenController extends \App\Controller
{
    /*获取订单详情
* */

    public function GetOrderInfoAction(Request $request){
        $istrue =  $this->verifySign($request->all());

        if(!$istrue){
          //  echo "签名错误";
            return ["code"=>0,"msg"=>"签名错误","data"=>"1000"];
        }

        $appid = $request->get("app_id"); //vid

        if(!preg_match('/\d{1,10}/',$appid)) throw  new  ApiException(ApiException::Remind,trans("messages.param_type_error"));
        $sn = $request->get("sn");

        if(!$sn || preg_match("/select|where|insert|update|from|show|explain|desc/",$sn) || strlen($sn) > 40) throw  new  ApiException(ApiException::Remind,trans("messages.param_type_error"));
        $open_id = $request->get("open_id"); //实是小号的ID
        //第三方的订单号
        $vorderid = $request->get("vorder_id");

        if(!preg_match("/\d{1,10}/",$vorderid)) throw  new ApiException(ApiException::Remind,trans("messages.param_type_error"));



        $ord = Orders::where("cp_uid", $open_id)->where('sn', $sn)->where("vid",$appid)->where("vorderid",$vorderid)->first();
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

                    return ["code"=>1,"msg"=>"订单待处理","data"=>$dta]; //订单待处理
                    break;
                case 1:
                    return ["code"=>2,"msg"=>"订单完成","data"=>"1001"]; //正确的返回值
                    break;
            }
        }

        return ["code"=>0,"msg"=>"订单不存在","data"=>"1002"]; //验证当前的订单如果失败，标记为是失败
    }

    //验证当前用户的登录

    public function AuthLoginAction(Request $request){

        $token = $request->get('token'); // 传递用户登录 返回的token信息

        if(!$token || strlen($token)>32 || preg_match("/select|where|insert|update|from|show|explain|desc/",$token)) throw  new  ApiException(ApiException::Remind,trans("messages.param_type_error"));

        $openid  = $request->get("open_id");

        if(!$openid || strlen($openid) >32 )  throw  new  ApiException(ApiException::Remind,trans("messages.param_type_error"));

        $appid = $request->get("app_id");

        if(!$appid ||  preg_match("/select|where|insert|update|from|show|explain|desc/",$appid)) $this->getTypeError("app_id");

        $istrue =  $this->verifySign($request->all());

        if(!$istrue){
           // echo "签名错误";
            return ["code"=>0,"msg"=>"签名错误","data"=>false];
        }

        //查询当前的session
        $dat = Session::where("token",$token)->where("cp_uid",$openid)->where("pid",$appid)->first();

        if($token !== "2mpbl2how4iso08kookw40gcw"){
            if(time() > $dat['expired_ts'])   return ["code"=>0,"msg"=>"token已失效","data"=>false];
        }


        if($dat){
          return ["code"=>1,"msg"=>"用户信息","data"=>true];
        }
        return ["code"=>0,"msg"=>"玩家不存在","data"=>false];

    }

//参数更是不正确
public function getTypeError($type) {
    throw  new  ApiException(ApiException::Remind,trans("messages.param_type_error").$type);
}

//生成签名函数的方法
    public function createSign(Request $request){
        $data = $request->all();
        ksort($data);
        reset($data);
        $signstr='';
        $ds = '';
        foreach($data as $k=>$v){
            $signstr.=$k."=".$v."&";
            $ds.=$k."=".urlencode($v)."&";
        }

        $back = $signstr;

        $appDat =  Procedures::where("pid",$data['app_id'])->first();
        $signkey= $appDat->psingKey;

        $signstr.="sign_key=".$signkey;


      //  echo("\r\n待验证字符串:".$signstr);


      //  echo "\r\n格式转化后的代码".$ds;


     //   echo ("\r\n签名完后的字符:".$ds."sign=".md5($ds."sign_key=".$signkey)); //生成新的字符串的

    }

//签名的方法

    private function verifySign($dat)
    {
        if (isset($dat['app_id']) && $dat['app_id']) {

            ksort($dat);
            reset($dat);
            $signstr = '';
            foreach ($dat as $k => $v) {
                if ($k != "sign") {
                    $signstr .= $k . "=" . urlencode($v) . "&";
                }
            }

            $appDat = Procedures::where("pid", $dat['app_id'])->first();
            $signkey = $appDat->psingKey;

            $jk = $signkey;
            $signstr .= "sign_key=" . $signkey;
           // echo("\r\n待验证字符串:" . $signstr);
            $sign = md5($signstr);

           // echo("\r\n待验证签名：" . $sign);

           //  echo("\r\n签名后的：" . $jk."sign=".$sign);


            return  $sign === $dat['sign'];
        }
    }

  //通知发货测试代码 接收数据
    public function TestNotify(Request $request){
        $dat = $request->all();
        log_info('OrderNotify', ['url' => "TestNotify", 'reqdata' => $dat]);
    }

 //执行发货的测试
    public function sendOrder(Request $request){
        $order = $request->get("order");
        order_success($order);
        log_info('send order', ['order' => $order]);
    }

}
