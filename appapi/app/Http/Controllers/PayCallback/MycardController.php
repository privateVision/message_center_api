<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class AlipayController extends Controller
{
    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['FacTradeSeq'];
    }

    protected function getTradeOrderNo($data, $order) {
        return $data['MyCardTradeNo'];
    }

    protected function verifySign($data, $order) {
        $config = config('common.payconfig.mycard');

        $hash = $data['hash'];
        unset($data['hash']);

        return static::mycard_hash($data, $config['FacServerKey']) === $hash;
    }

    protected function handler($data, $order){
        return @$data['PayResult'] == '3';
    }

    protected function onComplete($data, $order, $isSuccess) {
        return $isSuccess ? 'success' : 'fail';
    }

    protected static function mycard_hash($data, $key) {
        $prms = array_values($data);
        $prms[] = $key;
        $preStr = implode('', $prms);
        $hash = urlencode($preStr);

        $sign = hash('sha256', $hash, true);

        return bin2hex($sign);
    }
}