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
        $data['business'] = $config['business'];
        $data['product_name'] = $order->subject;
        $data['amount'] = $real_fee / 100;
        $data['order_no'] = $order->sn;
        $data['order_id'] = $order->id;

        return url('web/paypal/payment?sign=') . urlencode(encrypt3des(json_encode($data)));
    }
}