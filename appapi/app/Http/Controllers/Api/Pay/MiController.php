<?php

namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;

class MiController extends Controller
{
    const PayMethod = '-17';
    const PayText = 'xiaomi';
    const PayTypeText = '小米平台支付';

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
