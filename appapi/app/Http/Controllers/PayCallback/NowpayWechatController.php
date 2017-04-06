<?php
namespace App\Controller\External;

use App\Exceptions\PayCallbackException;
use Illuminate\Http\Request;
use App\Model\Orders;

class NowpayWechatController extends \App\Controller
{

    protected function getData(Request $request) {
        //$input = file_get_contents('php://input');
        //parse_str($input, $data);
        //return $data;
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['out_trade_no'];
    }

    protected function getTradeOrderNo($data, Orders $order) {
        return $data['trade_no'];
    }

    protected function verifySign($data, Orders $order) {
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

    protected function handler($data, Orders  $order){
        return true;
    }

    protected function onComplete($data, Orders $order, $isSuccess) {
        return $isSuccess ? 'success' : 'fail';
    }
}