<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class AlipayController extends Controller {

    use RequestAction;

    const PayType = '-1';
    const PayTypeText = '支付宝';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Orders $order, $real_fee) {
        $restype = $this->parameter->get('restype');

        $config = config('common.payconfig.alipay');

        $data = sprintf('partner="%s"', $config['AppID']);
        $data.= sprintf('&out_trade_no="%s"', $order->sn);
        $data.= sprintf('&subject="%s"', str_replace([' ', '　'], '', $order->subject));
        $data.= sprintf('&body="%s"', str_replace([' ', '　'], '', $order->body));
        $data.= sprintf('&total_fee="%.2f"', env('APP_DEBUG', true) ? 0.01 : $real_fee / 100);
        $data.= sprintf('&notify_url="%s"', urlencode(url('pay_callback/alipay')));
        $data.= '&service="mobile.securitypay.pay"';
        $data.= '&_input_charset="UTF-8"';
        $data.= '&payment_type="1"';
        $data.= sprintf('&seller_id="%s"', $config['AppID']);
        $data.= sprintf('&sign="%s"', urlencode(static::rsaSign($data, file_get_contents($config['PriKey']))));
        $data.= '&sign_type="RSA"';

        if($restype  == 'protocol') {
            $fromAppUrlScheme = $this->parameter->tough('scheme');
            $package = $this->parameter->tough('package');
            
            $data.= '&bizcontext="{"av":"1","ty":"ios_lite","appkey":"'.$config['AppID'].'","sv":"h.a.3.1.6","an":"'.$package.'"}"';

            $data = json_encode([
                'fromAppUrlScheme' => $fromAppUrlScheme,
                'requestType' => 'SafePay',
                'dataString' => $data,
            ]);

            return ['protocol' => sprintf('alipay://alipayclient/?%s', urlencode($data)), 'restype' => $restype];
        } else {
            return ['data' => $data];
        }
    }

    protected static function rsaSign($str, $prikey) {
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key);
        openssl_free_key($key);
        return base64_encode($sign);
    }
}