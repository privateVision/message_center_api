<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/21
 * Time: 8:44
 */

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class Register extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->get('/');

        $this->assertEquals(
            $this->app->version(), $this->response->getContent()
        );

        $this->get('/test',function(){
            //getToken
            $ispost = true;
            $key = "4c6e0a99384aff934c6e0a99";
            $token_url = "http://127.0.0.1/api/app/initialize";
            $dt_p = "imei=3447264d06ff60e9cc415229a0583a29&rid=255&device_code=3447264d06ff60e9cc415229a0583a29&device_name=iphone6plus&device_platform=16&version=1.0.0&app_version=1.1.0";
            $dats = sendrequest($token_url,$ispost,http_build_query(array('appid' => 778, 'param' =>encrypt3des($dt_p,$key))));
            $dt = json_decode($dats);

            if(isset($dt) && $dt->code == 0){
                $token = $dt->data->access_token;
                $sendurl  = "http://127.0.0.1/api/user/userRegister";
                $data  = "username=mll&password=123456&fh=13578658&access_token=".$token;
                $dat = sendrequest($sendurl,$ispost,http_build_query(array('appid' => 778, 'param' => encrypt3des($data,$key))));
                return $dat;
            }
        });


    }
}
