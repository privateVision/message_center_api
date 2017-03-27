<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;

trait CreateOrder {

    public function NewAction(Request $request, Parameter $parameter) {
        $order = new Orders;
        $order->getConnection()->beginTransaction();

        $order->ucid = $this->user->ucid;
        $order->uid = $this->user->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->createIP = $request->ip();
        $order->status = Orders::Status_WaitPay;
        $order->paymentMethod = '';
        $this->create_order($order, $request, $parameter);
        $order->save();

        $order_extend = new OrderExtend;
        $order_extend->order_id = $order->id;
        $order_extend->real_fee = 0;
        $order_extend->cp_uid = $this->session->cp_uid;
        $order_extend->save();

        $order->getConnection()->commit();

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'vip' => $this->user->vip,
            'balance' => $this->user->balance,
            'coupons' => $this->coupons($order),
        ];
    }

    abstract protected function create_order(Orders $order, Request $request, Parameter $parameter);
}