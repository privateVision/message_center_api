<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class NowpayWechatController extends Controller
{

    protected function getData(Request $request) {
        $input = file_get_contents('php://input');
        parse_str($input, $data);
        return $data;
        //return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['mhtOrderNo'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend) {
        return $data['channelOrderNo'] . "/" . $data['nowPayOrderNo'];
    }

    protected function verifySign($data, $order, $order_extend) {
        $config = config('common.payconfig.nowpay_wechat');

        $sign = $data['signature'];
        unset($data['signType'], $data['signature']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            if($v === '') continue;
            $str .= "{$k}={$v}&";
        }

        return md5($str . md5($config['secure_key'])) === $sign;
    }

    protected function handler($data, $order, $order_extend){
        return true;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null) {
        return $isSuccess ? 'success' : 'fail';
    }
}