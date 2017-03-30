<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;

class OrderController extends Controller {

    use CreateOrderAction;

    protected function onCreateOrder(Orders $order, Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');
        $vorderid = $parameter->tough('vorderid');
        $notify_url = $parameter->tough('notify_url');

        $order->notify_url = $notify_url;
        $order->vorderid = $vorderid;
        $order->fee = $fee;
        $order->subject = $subject;
        $order->body = $body;
    }
}