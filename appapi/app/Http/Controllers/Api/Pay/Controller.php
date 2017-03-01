<?php
namespace App\Http\Controllers\Api\Pay;

use App\Http\Controllers\Api\AuthController as BaseController;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class Controller extends BaseController {

    public function createOrder($fee, $body, $subject, $vorderid = '', $notify_url = '', $request) {
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

        return $order;
    }

    public function getOrder($order_id) {
        $order = Orders::where('sn', $order_id)->first();
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单状态不正确');
        }

        return $order;
    }
    
}