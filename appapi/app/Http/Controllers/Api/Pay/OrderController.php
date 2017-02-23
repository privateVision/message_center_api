<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class OrderController extends Controller {

    public function NewAction(Request $request, Parameter $parameter) {
        $fee = $parameter->tough('fee');
        $body = $parameter->tough('body');
        $subject = $parameter->tough('subject');
        $notify_url = $parameter->tough('notify_url');
        $vorderid = $parameter->tough('vorderid');

        $order = new Orders;
        $order->ucid = $this->ucuser->ucid;
        $order->uid = $this->ucuser->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->notify_url = $notify_url;
        $order->vorderid = $vorderid;
        $order->fee = $fee;
        $order->subject = $subject;
        $order->body = $body;
        $order->createIP = $request->ip();
        $order->status = Orders::Status_WaitPay;
        $order->paymentMethod = Orders::Way_Unknow;
        $order->hide = false;
        $order->save();

        return [
            'order_id' => $order->sn,
            'fee' => $fee,
            'way' => [
                1, 2, 3
            ],
        ];
    }
}