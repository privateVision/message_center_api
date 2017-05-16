<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;

class MycardController extends Controller {

    use RequestAction;

    const PayType = '-7';
    const PayTypeText = 'MyCard';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Orders $order, $real_fee) {
        $config = config('common.payconfig.mycard');
        
        $data['FacServiceId'] = $config['FacServiceId'];
        $data['FacTradeSeq'] = $order->sn;
        $data['TradeType'] = 'WEB';
        $data['ServerId'] = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $data['CustomerId'] = $order->ucid;
        $data['ProductName'] = $order->subject;
        $data['Amount'] = $real_fee;
        $data['Currency'] = 'TWD';
        $data['SandBoxMode'] = env('APP_DEBUG') ? true : false;
        
        $data['hash'] = mycard_hash();
        //$data['PaymentType'] = 
/*
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
*/
        $dt =  implode('&', $str);
        return ['data' => $dt];
    }

    protected static function mycard_hash($data) {
        ksort($data);
        $str = implode('', $data);
        $str = urlencode($str);
    }
}