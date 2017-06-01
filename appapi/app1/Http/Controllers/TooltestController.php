<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery\Generator\Parameter;
use Laravel\Lumen\Routing\Controller as BaseController;

class ToolTestController extends \App\Controller
{
    const BASEURL = 'www.sdkapi.com/';


    public function senurl($url,$data,$ispost=true){
        echo "------------------- {$url} -------------------<br/>";
        $ch = curl_init();         // 初始化
        echo "<strong>request data:</strong>";
        echo "<pre>";var_dump($data);echo "</pre>";

        curl_setopt($ch, CURLOPT_URL, static::BASEURL . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = curl_exec($ch);
        curl_close($ch);
        $res_data = json_decode($res, true);
        if(!$res_data) {
            echo "<span style=\"color:red\">返回值无法解析：$res</span><br/>";
            return false;
        }

        echo "<strong>response data:</strong>";
        echo "<pre>";var_dump($res_data);echo "</pre>";
        return $res_data['data'];
    }


    /*
 * send request
 * */

    public function sendrequest($callback,$ispost=false,$data= [])
    {
        echo "------------------- {$callback} -------------------<br/>";
        $ch = curl_init();         // 初始化
        echo "<strong>request data:</strong>";
        echo "<pre>";var_dump($data);echo "</pre>";

        try {

            date_default_timezone_set('PRC');
            $curlobj = curl_init();            // 初始化
            curl_setopt($curlobj, CURLOPT_URL, $callback);        // 设置访问网页的URL
            curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, true);            // 执行之后不直接打印出来
            curl_setopt($curlobj, CURLOPT_HEADER, 0);
            if ($ispost) {
                curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1); // 这样能够让cURL支持页面链接跳转
                curl_setopt($curlobj, CURLOPT_POST, 1);
                curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($curlobj, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded; charset=utf-8"));
            $output = curl_exec($curlobj);    // 执行
            curl_close($curlobj);// 关闭cURL
            $res_data = json_decode($output, true);
            if (!$res_data) {
                var_dump($res_data);
                echo "<span style=\"color:red\">返回值无法解析：($res_data)</span><br/>";
                return false;
            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }

        echo "<strong>response data:</strong>";
        echo "<pre>";var_dump($res_data);echo "</pre>";

        return $res_data['data'];
        //  $d = json_decode($output);
        //echo ($d->ret == 0)?json_encode(array("code"=>1,"msg"=>"成功")):json_encode(array("code"=>0,"msg"=>"发送失败"));
        // echo $output; //curl的方式获取当前的页面的信息
    }


    public function iosTestAction(Request $request){

        //$dat = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"12345678","notifyurl"=>"http://192.168.1.156:73/api/WithdrawAction/notify"];
         $dd = ["uid"=>"z80189495","_appid"=>"1001","password"=>"123456"];
        //$dd = ["mobile"=>18801273298,"_appid"=>1001];

        //$dd = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"t201703031808596173","notifyUrlBack"=>"http://dev.haochong.com/api/WithdrawAction/notifyUrlBack"];

        $ddt = [];
        $ddt['product_id'] = "com.anfeng.cqws600";
        $ddt['role_name'] = "浪子商";
        $ddt['ucid'] = 4819823;
        $ddt['uid'] = "hhtyh1717";
        $ddt['vorderid'] = 20170321181544;
        $ddt["zone_name"] = 57;
        $ddt["_sign"] = "rwwEZvBA3o0xFxEKQVCQ0SMmgz+2uUxs4xhgo87yRwAF2CcNpFszo4lT7SUvI8+g
MnZYlexsUywTg9YLnKTOrbqNZxCnonUmUSb3j6p+fMy20xKQmWYFSuVmgK//1J0C
5LqFyUAXRLTXAlTdKWmSg10QKZcRE9CqzfkOPPwHjng=";
        $ddt['_appid'] = 1336;
        $ddt['_token'] = 'asdadsads';

        //ksort($ddt);
        //$_token = md5(http_build_query($dd) . env('APP_' . @$dd['_appid']));

        //$dd['_token'] = $_token;

        $this->sendrequest(self::BASEURL."api/ios/order/create",true,$ddt);

    }

    public function Checkf(Request $request){
                $data = $request->all();
                $_sign = $data["sign"];

                unset($data["sign"]);
                 log_info('checkData', ['data' =>$data]);

                 $signkey='84ee7ad1a1c0e67c02d7c79418e532a0';

                ksort($data);
                $sign = md5(http_build_query($data) ."&sign_key={$signkey}");
                /*再追加字符串&signKey=signKey（此key值以安锋网向开发商提供）*/

                log_info('checkData', ['data' =>$sign]);

                if($sign == $_sign) return "SUCESS";

                return "FAILED";
    }


    public function SendOrder(Request $request){
        $order = $request->get("order");
        order_success($order);
    }







}