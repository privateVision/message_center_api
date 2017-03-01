<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery\Generator\Parameter;

class TestController extends \App\Controller
{
    const APPID = 778;
    const DESKEY = '4c6e0a99384aff934c6e0a99';
    const BASEURL = '127.0.0.1/';
    const RID = 255;

    protected $access_token = '';

    public function TestAction(Request $request ) {

        // ---------------- 从这里写测试代码 ----------------
        $dat = ["appid"=>1001,"amount"=>10,"username"=>"username"];
        ksort($dat);
        echo env('APP_' . @$dat['_appid']);
        $_token = md5(http_build_query($dat) . env('APP_' . @$dat['_appid']));

        $this->httpRequest("/tool/user/fpay",["appid"=>1001,"amount"=>10,"username"=>"z80189495","token"=>$_token]);
        echo $_token;

        exit();
        return ;
        date_default_timezone_set('Asia/Shanghai');

        // 初始化，并获取access_token
        $data = static::httpRequest('api/app/initialize', array(
            'imei' => '90012e76a270a94d34c38811c7db1ff3', 
            'rid' => static::RID, 
            'device_code' => '90012e76a270a94d34c38811c7db1ff3', 
            'device_name' => 'iphone 6plus', 
            'device_platform' => 16, 
            'version' => '1.0.0', 
            'app_version' => '1.1.0'
        ));

        if($data == false) return;

    }

    protected function httpRequest($uri, $data) {
        echo "------------------- {$uri} -------------------<br/>";
        $ch = curl_init();         // 初始化

        $data['access_token'] = $this->access_token;

        echo "<strong>request data:</strong>";
        echo "<pre>";var_dump($data);echo "</pre>";

        $postdata = array (
            'appid' => static::APPID,
            'param' => encrypt3des(http_build_query($data), static::DESKEY),
        );

        curl_setopt($ch, CURLOPT_URL, static::BASEURL . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        $res = curl_exec($ch);
        curl_close($ch);

        $res_data = json_decode($res, true);
        if(!$res_data) {
            echo "<span style=\"color:red\">返回值无法解析：{$res}</span><br/>";
            return false;
        }

        echo "<strong>response data:</strong>";
        echo "<pre>";var_dump($res_data);echo "</pre>";

        return $res_data['data'];
    }

    /*
     * 工具测试
     * */







}
