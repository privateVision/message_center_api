<?php

namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;

class OppoController extends Controller
{
    use RequestAction;

    const PayMethod = '-16';
    const PayText = 'oppo';
    const PayTypeText = 'oppo平台支付';

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
            'data' => array()
        ];
    }
}
