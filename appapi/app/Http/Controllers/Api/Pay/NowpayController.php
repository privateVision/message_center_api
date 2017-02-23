<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class NowpayController extends Controller {

    public function WechatAction(Request $request, Parameter $parameter) {
        $order_id = $parameter->tough('order_id');

        $order = Orders::where('sn', $order_id)->first();
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单状态不正确');
        }

        $config = config('common.nowpay.wechat');

        $mht['appId'] = $config['appId'];
        //$mht['consumerId'] = $this->ucuser->ucid;
        //$mht['consumerName'] = $this->ucuser->uid;
        $mht['mhtCharset'] = $config['mhtCharset'];
        $mht['mhtCurrencyType'] = $config['mhtCurrencyType'];
        $mht['mhtOrderAmt'] = $order->fee();
        $mht['mhtOrderName'] = $order->subject;
        $mht['mhtOrderDetail'] = $order->body;
        $mht['mhtOrderNo'] = $order->sn;
        $mht['mhtOrderStartTime'] = date($config['dtFormat']);
        //$mht['mhtOrderTimeOut'] = $config['mhtOrderTimeOut']
        $mht['mhtOrderType'] = $config['mhtOrderType'];
        $mht['notifyUrl'] = url('pay_callback/nowpay_wechat');
        $mht['payChannelType'] = $config['payChannelType'];
        ksort($mht);
        $mht['mhtSignature'] = md5(static::encode($mht) .'&'. md5($config['secure_key']));
        $mht['mhtSignType'] = $config['mhtSignType'];

        return ['data' => static::encode($mht)];
    }

    public function AlipayAction(Request $request, Parameter $parameter) {
        $order_id = $parameter->tough('order_id');

        $order = Orders::where('sn', $order_id)->first();
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单状态不正确');
        }

        $config = config('common.nowpay.alipay');

        $mht['appId'] = $config['appId'];
        //$mht['consumerId'] = $this->ucuser->ucid;
        //$mht['consumerName'] = $this->ucuser->uid;
        $mht['mhtCharset'] = $config['mhtCharset'];
        $mht['mhtCurrencyType'] = $config['mhtCurrencyType'];
        $mht['mhtOrderAmt'] = $order->fee();
        $mht['mhtOrderName'] = $order->subject;
        $mht['mhtOrderDetail'] = $order->body;
        $mht['mhtOrderNo'] = $order->sn;
        $mht['mhtOrderStartTime'] = date($config['dtFormat']);
        //$mht['mhtOrderTimeOut'] = $config['mhtOrderTimeOut']
        $mht['mhtOrderType'] = $config['mhtOrderType'];
        $mht['notifyUrl'] = url('pay_callback/nowpay_alipay');
        $mht['payChannelType'] = $config['payChannelType'];
        ksort($mht);
        $mht['mhtSignature'] = md5(static::encode($mht) .'&'. md5($config['secure_key']));
        $mht['mhtSignType'] = $config['mhtSignType'];

        return ['data' => static::encode($mht)];
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