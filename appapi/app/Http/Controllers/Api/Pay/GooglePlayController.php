<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class GooglePlayController extends Controller {

    use RequestAction;

    const PayMethod = '-8';
    const PayText = 'googleplay';
    const PayTypeText = 'GooglePlay平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, $real_fee, Request $request) {
        return [
            'data' => array()
        ];
    }


}