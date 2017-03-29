<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class FController extends Controller {

    use RequestAction, CreateOrderAction;

    const PayType = '-1';
    const PayTypeText = 'F币或卡券直接支付';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Request $request, Parameter $parameter, Orders $order, $real_fee) {
        if($real_fee > 0) {
            throw new ApiException(ApiException::Remind, '不能使用余额直接抵扣');
        }
        
        order_success($order->id);

        return ['result' => true];
    }

    protected function createOrderBefore(Orders $order, Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');

        $order->fee = $fee;
        $order->subject = $subject;
        $order->body = $body;
    }
}