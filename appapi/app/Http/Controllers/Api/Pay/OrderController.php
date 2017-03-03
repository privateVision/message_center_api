<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;

class OrderController extends Controller {

    public function NewAction(Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');
        $vorderid = $parameter->tough('vorderid');
        $notify_url = $parameter->tough('notify_url');

        $order = $this->createOrder($fee, $body, $subject, $request->ip(), $vorderid, $notify_url);

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'vip' => $this->ucuser->vip(),
            'balance' => $this->ucuser->balance,
            'coupon' => $this->ucuser->coupon(),
        ];
    }

    public function AnfengNewAction(Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');

        $order = $this->createOrder($fee, $body, $subject, $request->ip());

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'vip' => $this->ucuser->vip(),
            'balance' => $this->ucuser->balance,
            'coupon' => $this->ucuser->coupon(),
        ];
    }
}