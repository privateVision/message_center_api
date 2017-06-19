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
        $amount = exchange_rate($real_fee, 'USD');
        if(!$amount || $amount === '0.00') {
            // 正常情况不会走到这里，非正常情况就是不怀好意的人，还给他个提示？啊呸！！！
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        // 为了节省费率，大于12美元使用另一帐户收款
        if(($amount * 100) >= 1200) {
            $config['ClientID'] = $config['account_2']['ClientID'];
            $config['Secret'] = $config['account_2']['Secret'];
            $config['business'] = $config['account_2']['business'];
            $account = 'account_2';
        } else {
            $config['ClientID'] = $config['account_1']['ClientID'];
            $config['Secret'] = $config['account_1']['Secret'];
            $config['business'] = $config['account_1']['business'];
            $account = 'account_1';
        }

        $order_extend->extra_params = ['account' => $account];

        // get token
        $headers = [
            'Accept: application/json',
            'Accept-Language: en_US',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['access_token_url']);
        curl_setopt($ch, CURLOPT_USERPWD, "{$config['ClientID']}:{$config['Secret']}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        curl_close($ch);

        log_info('paypal-ec-get_access_token', ['reqdata' => 'grant_type=client_credentials', 'resdata' => $response], $config['access_token_url']);

        //$response = http_curl($config['access_token_url'], 'grant_type=client_credentials', true, array(
        //    CURLOPT_USERPWD=>"{$config['ClientID']}:{$config['Secret']}",
        //    CURLOPT_HTTPHEADER=>$headers,
        //    CURLOPT_CONNECTTIMEOUT=>60
        //), 'str');

        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        $response = json_decode($response, true);
        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        // {"error":"invalid_token","error_description":"Authorization header does not have valid access token"}
        if(@$response['error']) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        /*{
        "scope":"https://uri.paypal.com/services/subscriptions https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://uri.paypal.com/services/applications/webhooks openid https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.*",
        "nonce":"2017-06-13T06:43:40Z-jP71-Rzp1fuHjqVOfGMf0_opkAHJn4zkekuiwy9BUI",
        "access_token":"A21AAGOZIb9sn2l8ihIBfPayfabsRI5kzalUD08I24bz864Rih9aiJ78ylpfqtnLulv9kwWg0UAG093OuyG_8R8Ds7PzcjvZw",
        "token_type":"Bearer",
        "app_id":"APP-80W284485P519543T",
        "expires_in":32059
        }*/
        $access_token = $response['access_token'];

        $data['account'] = $account;
        $data['order_no'] = $order->sn;
        $data['access_token'] = $access_token;

        $data['type'] = 'return';
        $return_url = url('pay_callback/paypalec?sign=') . urlencode(encrypt3des(json_encode($data)));

        $data['type'] = 'cancel';
        $cancel_url = url('pay_callback/paypalec?sign=') . urlencode(encrypt3des(json_encode($data)));

        $data =<<<JSON
{
    "intent":"sale",
    "redirect_urls":{
        "return_url":"{$return_url}",
        "cancel_url":"{$cancel_url}"
    },
    "payer":{
        "payment_method":"paypal"
    },
    "transactions":[
        {
            "amount":{
                "total":"{$amount}",
                "currency":"USD"
            }
        }
    ]
}
JSON;

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $access_token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['payment_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $response = curl_exec($ch);
        curl_close($ch);

        log_info('paypal-ec-payment', ['reqdata' => $data, 'resdata' => $response], $config['payment_url']);

//        $response = http_curl($config['payment_url'], $data, true, array(
//            CURLOPT_HTTPHEADER=>$headers,
//            CURLOPT_CONNECTTIMEOUT=>60
//        ), 'str');

        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        $response = json_decode($response, true);
        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        if(@$response['error']) {
            throw new ApiException(ApiException::Remind, trans("pay_fail"));
        }

        foreach($response['links'] as $k => $v) {
            $response['links'][$v['rel']] = $v;
        }

        // 将信息保存起来，有用的
        $order_extend->extra_params = ['payment' => $response];

        return $response['links']['approval_url']['href'];
    }
}