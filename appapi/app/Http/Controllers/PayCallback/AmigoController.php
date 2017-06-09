<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class AmigoController extends Controller
{
    protected function getData(Request $request)
    {
        return $_POST;
    }

    protected function getOrderNo($data)
    {
        return $data['requestId'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['orderId'];
    }

    protected function verifySign($data, $order, $order_extend)
    {
        $config = ProceduresExtend::where('pid', $order->vid)->first()->toArray;

        $content = "";
        $i = 0;
        foreach($data as $key=>$value)
        {
            if($key != "sign" )
            {
                $content .= ($i == 0 ? '' : '&').$key.'='.$value;
            }
            $i++;
        }

        $pubKey = @file_get_contents($config['third_appsecret']);
        $openssl_public_key = @openssl_get_publickey($pubKey);

        $ok = @openssl_verify($content,base64_decode($data['sign']), $openssl_public_key);
        @openssl_free_key($openssl_public_key);
        if($ok) {
            return true;
        }

        return false;
    }

    protected function handler($data, $order, $order_extend)
    {
        //验证支付成功
        if($data['result'] === 0) {
            return true;
        }
        return false;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess)
    {
        return json_encode(array(
            "result"=>$isSuccess?0:1
        ));
    }
}
