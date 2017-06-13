<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class LenovoController extends Controller
{
    /**
     * lenovo config
     * {
     *      "app_id":"1702150608789.app.ln",
     *      "app_key":"MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBALKN2hE/ke/IP4KptsroyNMeraqBg6lhs3IUflhfXb3mPPGd7R8VCjYsBwzw4MvsdnQYzxn3LYus5kg2J8u0y7+bk41oobhKM/7hPqRmq0p2DYw7lvfhhRTp+5icjn0JRTmAx4xiZ80+Px8AwZ7hQFZUHJf0Ogbx9ZgTrg0C43zVAgMBAAECgYALPQx1u3eXDRaaRc5glShWyX6K1d4QojqmOo39R/thgYVie9s58pwS7tB+ywaLL1YBVrJqYvl16isQboAwvS95w/d+8UEZ78P1mVTAcVp4xNRGvMS4C+wNVLJYa/0GNAjDvxp6qr6ZTKO5Ta5Dp1lIXB7oqLVQ5D5sbZUqxPBNfQJBAOYXZ5WZhl5NfgkP9WL2LDFEtQyhbS/d5k4pdZ/ckvxtjQ323ioFMXIIuEl/DklqBU4ZBoDa4mwUN33CAQaFbT8CQQDGqNZHNO3JhnkRoPOF2phiogR8IlxrRSw0MyFKbeJJ+WLaUXh2Pj0EMJIx+wzcyltc0e7wDY7MTLNP4XmEJszrAkAXGuCO+DSzAYsXc9/LSTcU13Zqx0cEmH7I+IbUP70O1h1k+pZCl/ToI5IF51lS6++OcRrjE5fLDJip6zJZKkrXAkBSf6r8xy44km+UspJu8+h0jXPvWRWoNoG068bXceqXbclvgIXWFOKh6snLl8YvqplmYogniHnUvcV5Vtlv1+0hAkBo5DWxKlhnR3ijTL77vCACGOSsXnV9LueuEKYrVSmjJ1CQ628EHXJW4BN90KbQw+NhkROKOWbSGtcpoEkuz9I6"
     * }
     */
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
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();
        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_key'])) {
            return false;
        }

        $selfSign = self::sign($data['transdata'], $cfg['app_key']);
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
            $priKey = wordwrap($priKey, 64, "\n", true);
            $priKey = "-----BEGIN RSA PRIVATE KEY-----\n".$priKey."\n-----END RSA PRIVATE KEY-----";
        }
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

}
