<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;

trait VerifyAction {

    public function VerifyAction() {
        $order_sn = $this->parameter->tough('order_id');

        $order = Orders::from_cache_sn($order_sn);
        if(!$order) {
            throw new ApiException(ApiException::Remind, trans('messages.order_not_exists'));
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, trans('messages.order_already_success'));
        }
    }

    abstract protected function getData();
    abstract protected function getOrderNo();
    abstract protected function getTradeOrderNo();
    abstract protected function verify();
}