<?php
namespace App\Http\Controllers\Api\Pay;

use App\Http\Controllers\Api\AuthController as BaseController;
use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrdersExt;

class Controller extends BaseController {

    public function payOrder($order_id, $paymentMethod, $balance = 0) {
        $order = Orders::where('sn', $order_id)->first();
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单状态不正确');
        }

        $order->paymentMethod = $paymentMethod;
        $order->save();

        if($balance > 0) {
            if($this->ucuser->balance < $balance) {
                throw new ApiException(ApiException::Remind, '爪币余额不足');
            }
            
            $ordersExt = new OrdersExt;
            $ordersExt->oid = $order->id;
            $ordersExt->vcid = 0;
            $ordersExt->fee = $balance;
            $ordersExt->save();
        }

        return $order;
    }
}