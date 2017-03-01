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

        $order = $this->createOrder($fee, $body, $subject, $vorderid, $notify_url, $request);

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
        ];
    }

    public function AnfengNewAction(Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');

        $order = $this->createOrder($fee, $body, $subject, '', '', $request);

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
        ];
    }
}