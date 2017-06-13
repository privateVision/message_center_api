<?php

namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class OppoController extends Controller
{
    use RequestAction;

    const PayMethod = '-16';
    const PayText = 'oppo';
    const PayTypeText = 'oppo平台支付';

    /**
     * oppo config
     * {
     *      "app_id":"2183622",
     *      "app_key":"81tKZhcpxI0wOoGgSwcgwk0WC",
     *      "app_secret":"2a838e1Aaef9412e5412d511a644a5b3",
     *      "pay_pub_key":"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmreYIkPwVovKR8rLHWlFVw7YDfm9uQOJKL89Smt6ypXGVdrAKKl0wNYc3/jecAoPi2ylChfa2iRu5gunJyNmpWZzlCNRIau55fxGW0XEu553IiprOZcaw5OuYGlf60ga8QT6qToP0/dpiL/ZbmNUO9kUhosIjEu22uFgR+5cYyQIDAQAB"
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param OrderExtend $order_extend
     * @param $real_fee
     * @return array
     * @internal param $accountId
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        return [
            'data' => array(
                'order'=>$order->sn,
                'amount'=>$real_fee,
                'productName'=>$order->subject,
                'productDesc'=>$order->body,
                'callbackUrl'=>url('pay_callback/oppo')
            )
        ];
    }
}
