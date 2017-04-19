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

        if(!$istrue) throw new  ApiException(ApiException::Remind,"TYPE ERROR");

        $appid = $request->get("appid"); //vid

        if(!preg_match('/\d{1,10}/',$appid)) throw  new  ApiException(ApiException::Remind,trans("messages.order_info_error"));
        $sn = $request->get("sn");

        $sub_uid = $request->get("ucid"); //其实是小号的ID
        //第三方的订单号
        // $vorderid = $request->post("vorderid");

        $ord = Orders::where("ucid", $sub_uid)->where('sn', $sn)->where("vid",$appid)->first();
        if ($ord) { //拼接返回的数据

            return "SUCESS";
        }

        return "FALSE"; //验证当前的订单如果失败，标记为是失败
    }

    //验证当前用户的登录

    public function AuthLoginAction(Request $request){
        $istrue =  $this->verifySign($request->all());
        if(!$istrue) throw  new  ApiException(ApiException::Remind,trans("messages.order_info_error"));

        $token = $request->get('uuid'); // 传递用户登录 返回的token信息
        $ucid  = $request->get("ucid");
        $appid = $request->get("appid");
        //查询当前的session
        $dat = Session::where("token",$token)->where("ucid",$ucid)->where("pid",$appid)->first();

        if(time() > $dat['expired_ts']) return "FALSE";

        if($dat){
            return "SUCESS";
        }

        return "FALSE";
    }


//生成签名函数的方法
    public function createSign(Request $request){
        $data = $request->all();
        ksort($data);
        reset($data);
        $signstr='';
        foreach($data as $k=>$v){
            $signstr.=$k."=".$v."&";
        }

        $appDat =  Procedures::where("pid",$data['appid'])->first();
        $signkey= $appDat->psingKey;
        $signstr.="signKey=".$signkey;
        echo("\r\n待验证字符串:".$signstr);
        printf("sign:%s",md5($signstr));
    }

//签名的方法

    private function verifySign($dat)
    {
        if (isset($dat['appid']) && $dat['appid']) {

            ksort($dat);
            reset($dat);
            $signstr = '';
            foreach ($dat as $k => $v) {
                if ($k != "sign") {
                    $signstr .= $k . "=" . $v . "&";
                }
            }

            $appDat = Procedures::where("pid", $dat['appid'])->first();
            $signkey = $appDat->psingKey;
            $signstr .= "signKey=" . $signkey;
            //echo("\r\n待验证字符串:" . $signstr);
            $sign = md5($signstr);

          //  echo("\r\n待验证签名：" . $sign);

            return  $sign === $dat['sign'];
        }
    }

}
