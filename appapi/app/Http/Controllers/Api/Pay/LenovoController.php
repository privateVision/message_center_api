<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class LenovoController extends Controller {

    use RequestAction;

    const PayMethod = '-14';
    const PayText = 'lenovo';
    const PayTypeText = '联想平台支付';

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