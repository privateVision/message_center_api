<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class HuaWeiController extends Controller
{
    protected function getData(Request $request)
    {
        $request = file_get_contents("php://input");

        $elements = explode('&', $request);
        $valueMap = array();
        foreach ($elements as $element)
        {
            $single = explode('=', $element);
            $valueMap[$single[0]] = $single[1];
        }

        if(null !== $valueMap["sign"])
        {
            $valueMap["sign"] = urldecode($valueMap["sign"]);
        }
        if(null !== $valueMap["extReserved"])
        {
            $valueMap["extReserved"]= urldecode($valueMap["extReserved"]);
        }
        if(null !== $valueMap["sysReserved"])
        {
            $valueMap["sysReserved"] = urldecode($valueMap["sysReserved"]);
        }

        ksort($valueMap);

        return $valueMap;
    }

    protected function getOrderNo($data)
    {
        return $data['requestId'];
    }

    protected function getTradeOrderNo($data, $order)
    {
        return $data['orderId'];
    }

    protected function verifySign($data, $order)
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

    protected function onComplete($data, $order, $isSuccess)
    {
        return json_encode(array(
            "result"=>$isSuccess?0:1
        ));
    }
}
