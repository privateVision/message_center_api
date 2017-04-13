<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class FController extends Controller {

    use RequestAction, CreateOrderAction;

    const PayType = '0';
    const PayTypeText = 'F币或卡券直接支付';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;
    const FSIGN = 2;

    public function payHandle(Orders $order, $real_fee) {
        if($real_fee > 0) {
            throw new ApiException(ApiException::Remind, '不能使用余额或优惠券直接抵扣');
        }
        
        order_success($order->id);

        return ['result' => true];
    }

    protected function onCreateOrder(Orders $order) {
        $fee = $this->parameter->tough('fee');
        $body = $this->parameter->tough('body');
        $subject = $this->parameter->tough('subject');
        $order->vid = self::FSIGN;
        $order->fee = $fee;
        $order->subject = $subject;
        $order->body = $body;
    }
}