<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Ucusers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery\Generator\Parameter;

class TestController extends \App\Controller
{
    const APPID = 778;
    const DESKEY = '4c6e0a99384aff934c6e0a99';
    const BASEURL = '192.168.1.116/';
    const RID = 255;

    protected $access_token = '';

    public function TestAction(Request $request ) {

        // ---------------- 从这里写测试代码 ----------------

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
        $usename = $this->httpRequest("api/account/username",$data);

    }

    protected function httpRequest($uri, $data) {
        echo "------------------- {$uri} -------------------<br/>";
        $ch = curl_init();         // 初始化

        $data['access_token'] = $this->access_token;

     //   echo "<strong>request data:</strong>";
       // echo "<pre>";var_dump($data);echo "</pre>";

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

        //echo "<strong>response data:</strong>";
       // echo "<pre>";var_dump($res_data);echo "</pre>";

        return $res_data['data'];
    }

    /*
     * 工具测试
     * */

    public function UsernameAction() {
        $username = null;

        $chars = 'abcdefghjkmnpqrstuvwxy';
        do {
            $username = $chars[rand(0, 21)] . rand(10000, 99999999);
            $count = Ucusers::where('uid', $username)->count();
            if($count ==0 ) return ['username' => $username];
        } while(true);

    }

    //创建用户

    public function createUserAction(Request $request){

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

        $username = $this->UsernameAction();
        $username   = $username['username'] ;
        $password   = "123456";

        if(!check_name($username, 24)){
            throw new ApiException(ApiException::Remind, "用户名格式不正确，请填写正确的格式");
        }

        $isRegister  = Ucusers::where("mobile", $username)->orWhere('uid', $username)->count();

        if($isRegister) {
            throw new  ApiException(ApiException::Remind, "用户已注册，请直接登陆");
        }

        $UcenterMember = new UcenterMembers;
        $UcenterMember->password = $password;
        $UcenterMember->email = $username . "@anfan.com";;
        $UcenterMember->regip = $request->ip();
        $UcenterMember->username = $username;
        $UcenterMember->regdate = time();
        $uucid =$UcenterMember->save();

        $ucuser = $UcenterMember->ucusers()->create([
            'ucid' =>$uucid,
            'uid' => $username,
            'uuid' => $data['access_token'],
        ]);

        echo "username:".$username."   password: 123456";
        return ;

    }



}
