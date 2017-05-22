<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class BaiduController extends Controller {

    use RequestAction;

    const PayMethod = '-10';
    const PayText = 'baidu';
    const PayTypeText = '百度平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, $real_fee, OrderExtend $order_extend, Request $request) {
        return [
            'data' => array()
        ];
    }


}