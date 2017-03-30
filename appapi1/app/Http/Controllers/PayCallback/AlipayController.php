<?php
namespace App\Controller\External;

use App\Exceptions\PayCallbackException;
use Illuminate\Http\Request;
use App\Model\Orders;

class AlipayController extends \App\Controller
{
    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['out_trade_no'];
    }

    protected function getTradeOrderNo($data, Orders $order) {
        return $data['trade_no'];
    }

    protected function verifySign($data, Orders $order) {
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
        
        return rsaVerify($str, $config['PubKey'], $sign);
    }

    protected function handler($data, Orders  $order){
        return true;
    }

    protected function onComplete($data, Orders $order, $isSuccess) {
        return $isSuccess ? 'success' : 'fail';
    }

    protected function rsaVerify($data, $ali_public_key_path, $sign)  {
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);    
        return $result;
    }
}