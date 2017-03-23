<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;

class AlipayController extends PayController {

    const PayType = '-1';
    const PayTypeText = '支付宝';

    public function handle(Request $request, Parameter $parameter, $order, $real_fee) {
        $config = config('common.payconfig.alipay');

        $data = sprintf('partner="%s"', $config['AppID']);
        $data.= sprintf('&out_trade_no="%s"', $order->sn);
        $data.= sprintf('&subject="%s"', $order->subject);
        $data.= sprintf('&body="%s"', $order->body);
        $data.= sprintf('&total_fee="%.2f"', env('APP_DEBUG', true) ? 0.01 : $order->real_fee);
        $data.= sprintf('&notify_url="%s"', urlencode(url('pay_callback/nowpay_alipay')));
        $data.= '&service="mobile.securitypay.pay"';
        $data.= '&_input_charset="UTF-8"';
        $data.= '&payment_type="1"';
        $data.= sprintf('&seller_id="%s"', $config['AppID']);
        $data.= sprintf('&sign="%s"', urlencode(static::rsaSign($data, file_get_contents($config['PriKey']))));
        $data.= '&sign_type="RSA"';

        return ['data' => $data];
    }

    protected static function rsaSign($str, $prikey) {
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key);
        openssl_free_key($key);
        return base64_encode($sign);
    }
}