<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class PaypalController extends Controller {

    use RequestAction;

    const PayMethod = '-19';
    const PayText = 'paypal';
    const PayTypeText = 'PayPal';

    public function getUrl($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $debug = env('APP_DEBUG', true);
    }

    protected function getAccessToken($config) {
        $url = env('APP_DEBUG', true) ? 'https://api.sandbox.paypal.com/v1/oauth2/token' : 'https://api.paypal.com/v1/oauth2/token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['grant_type' => 'client_credentials']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept' => 'application/json', 'Accept-Language' => 'en_US', 'Authorization' => $config['ClientID'] .' '. $config['Secret']]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $res = curl_exec($ch);
        curl_close($ch);
    }

    protected function HTTPRequest() {
        $data = http_build_query($data);

        if(!$is_post) {
            $url = strpos($url, '?') == -1 ? ($url .'?'. $data) : ($url .'&'. $data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // is https
        if (stripos($url,"https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        // is post
        if($is_post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //超时限制
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}