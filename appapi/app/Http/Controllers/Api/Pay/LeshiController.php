<?php

namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;

class LeshiController extends Controller
{
    use RequestAction;

    const PayMethod = '-18';
    const PayText = 'leshi';
    const PayTypeText = '乐视平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        return [
            'data' => array()
        ];
    }
}
