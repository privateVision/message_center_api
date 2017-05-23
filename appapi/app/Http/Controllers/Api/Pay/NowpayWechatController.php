<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;

class NowpayWechatController extends Controller {

    use RequestAction;

    const PayMethod = '-5';
    const PayText = 'nowpay_wechat';
    const PayTypeText = '微信';

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $mht['appId'] = $config['appId'];
        $mht['mhtCharset'] = $config['mhtCharset'];
        $mht['mhtCurrencyType'] = $config['mhtCurrencyType'];
        $mht['mhtOrderAmt'] = env('APP_DEBUG', true) ? 1 : $real_fee;
        $mht['mhtOrderName'] = $order->subject;
        $mht['mhtOrderDetail'] = $order->body;
        $mht['mhtOrderNo'] = $order->sn;
        $mht['mhtOrderStartTime'] = date($config['dtFormat']);
        $mht['mhtOrderType'] = $config['mhtOrderType'];
        $mht['notifyUrl'] = url('pay_callback/nowpay_wechat');
        $mht['payChannelType'] = $config['payChannelType'];
        ksort($mht);

        $mht['mhtSignature'] = md5(static::encode($mht) .'&'. md5($config['secure_key']));
        $mht['mhtSignType'] = $config['mhtSignType'];

        $str = [];
        foreach($mht as $k => $v) {
            if($v == "") continue;
            $str[] = "{$k}=".urlencode($v);
        }

        $dt =  implode('&', $str);

        return ['data' => $dt];
    }

    protected static function encode($data) {
        ksort($data);
        $str = [];
        foreach($data as $k => $v) {
            if($v == "") continue;
            $str[] = "{$k}={$v}";
        }

        return implode('&', $str);
    }
}