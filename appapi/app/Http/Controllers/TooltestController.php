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


    public function senurl($url,$data){

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



    public function fpayTestAction(Request $request){

        //$dat = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"12345678","notifyurl"=>"http://192.168.1.156:73/api/WithdrawAction/notify"];
         $dd = ["username"=>"z80189495","_appid"=>"1001","password"=>"123456"];
        //$dd = ["_appid"=>1001,"amount"=>10,"username"=>"z80189495","sn"=>"t201703031808596173","notifyUrlBack"=>"http://192.168.1.156:73/api/WithdrawAction/notifyUrlBack"];
        ksort($dd);
        $_token = md5(http_build_query($dd) . env('APP_' . @$dd['_appid']));

        $dd['_token'] = $_token;
        $this->senurl("/tool/user/auth",$dd);

    }






}