<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery\Generator\Parameter;
use Laravel\Lumen\Routing\Controller as BaseController;

class ToolTestController extends \App\Controller
{
    const BASEURL = '192.168.1.116/';


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


        date_default_timezone_set('PRC');
        $curlobj = curl_init();			// 初始化
        curl_setopt($curlobj, CURLOPT_URL, $callback);		// 设置访问网页的URL
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, true);			// 执行之后不直接打印出来
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        if($ispost){
            curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1); // 这样能够让cURL支持页面链接跳转
            curl_setopt($curlobj, CURLOPT_POST, 1);
            curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curlobj, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded; charset=utf-8"));
        $output=curl_exec($curlobj);	// 执行
        curl_close($curlobj);// 关闭cURL

        $res_data = json_decode($output, true);
        if(!$res_data) {
            echo "<span style=\"color:red\">返回值无法解析：$output</span><br/>";
            return false;
        }

        echo "<strong>response data:</strong>";
        echo "<pre>";var_dump($res_data);echo "</pre>";


        return $res_data['data'];
        //  $d = json_decode($output);
        //echo ($d->ret == 0)?json_encode(array("code"=>1,"msg"=>"成功")):json_encode(array("code"=>0,"msg"=>"发送失败"));
        // echo $output; //curl的方式获取当前的页面的信息
    }


    public function fpayTestAction(Request $request){

        //$dat = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"12345678","notifyurl"=>"http://192.168.1.156:73/api/WithdrawAction/notify"];
         $dd = ["uid"=>"z80189495","_appid"=>"1001","password"=>"123456"];
        //$dd = ["mobile"=>18801273298,"_appid"=>1001];

        //$dd = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"t201703031808596173","notifyUrlBack"=>"http://dev.haochong.com/api/WithdrawAction/notifyUrlBack"];
        ksort($dd);
        $_token = md5(http_build_query($dd) . env('APP_' . @$dd['_appid']));

        $dd['_token'] = $_token;

        $this->sendrequest(self::BASEURL."tool/user/freeze",true,$dd);

    }






}