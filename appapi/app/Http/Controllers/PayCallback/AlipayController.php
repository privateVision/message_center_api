<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class AlipayController extends Controller
{

    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['out_trade_no'];
    }

    protected function getTradeOrderNo($data, $order) {
        return $data['trade_no'];
    }

    protected function verifySign($data, $order) {
        $config = config('common.payconfig.alipay');

        $sign = $data['sign'];
        unset($data['sign'], $data['sign_type']);

        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            if($v === '') continue;
            $str .= "{$k}={$v}&";
        }
        $str = trim($str, '&');
        
        return static::rsaVerify($str, $config['PubKey'], $sign);
    }

    protected function handler($data, $order){
        return $data['trade_status'] == 'TRADE_SUCCESS';
    }

    protected function onComplete($data, $order, $isSuccess) {
        return $isSuccess ? 'success' : 'fail';
    }

    protected static function rsaVerify($data, $ali_public_key_path, $sign)  {
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);    
        return $result;
    }
}