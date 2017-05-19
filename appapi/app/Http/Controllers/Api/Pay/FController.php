<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;

class FController extends Controller {

    use RequestAction;

    const PayType = '0';
    const PayText = 'fb';
    const PayTypeText = 'F币或卡券直接支付';

    public function getData($config, Orders $order, $real_fee) {
        if($real_fee > 0) {
            throw new ApiException(ApiException::Remind, trans('messages.order_not_use_f'));
        }

        if($order->status == Orders::Status_WaitPay) {
            order_success($order->id);
        }

        return ['result' => true];
    }
}