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

        // start

        $data = $this->getData();

        if(!$this->verify($data, $order)) {
            throw new ApiException(ApiException::Remind, trans('messages.order_verify_fail'));
        }

        $trade_order_no = $this->getTradeOrderNo($data, $order);

        $order_extend = OrderExtend::find($order->id);
        $order_extend->third_order_no = $trade_order_no;
        $order_extend->save();

        if($this->handler()) {
            $order->callback_ts = time();
            $order->save();

            order_success($order->id);
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.order_handle_fail'));
        }

        return ['result' => true];
    }

    abstract protected function getTradeOrderNo($data);
    abstract protected function handler($data, Orders $order);
    abstract protected function getData();
    abstract protected function getFee();
}