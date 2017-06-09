<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class LenovoController extends Controller
{
    protected function getData(Request $request)
    {
        $values = $_POST;
        if(isset($values['transdata'])) {
            $values = array_merge($values, json_decode($values['transdata'], true));
        }

        return $values;
    }

    protected function getOrderNo($data)
    {
        return $data['exorderno'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['transid'];
    }

    protected function verifySign($data, $order, $order_extend)
    {
        $result = ProceduresExtend::where('pid', $order->vid)->first()->toArray;

        $selfSign = self::sign($data['transdata'], $result['third_payprikey']);
        if($selfSign == $data['sign']){
            return true;
        }

        return false;
    }

    protected function handler($data, $order, $order_extend)
    {
        if($data['result'] == 1) {
            return true;
        }

        return false;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null)
    {
        return $isSuccess?'SUCCESS':'FAILTURE';
    }

    /**
     * RSA签名
     * @param $data   待签名数据
     * @param $priKey 密钥
     * return 签名结果
     */
    protected function sign($data, $priKey) {
        if(strpos($priKey, "BEGIN RSA PRIVATE KEY") === false)
        {
            //$priKey = wordwrap($priKey, 64, "\n", true);
            //$priKey = "-----BEGIN PRIVATE KEY-----\n".$priKey."\n-----END PRIVATE KEY-----";
        }
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

}
