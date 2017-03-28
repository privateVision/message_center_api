<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
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
        $this->create_order_before($order, $request, $parameter);
        $order->save();

        $order_extend = new OrderExtend;
        $order_extend->order_id = $order->id;
        $order_extend->real_fee = 0;
        $order_extend->cp_uid = $this->session->cp_uid;
        $order_extend->save();

        $result = $this->create_order_after($order, $request, $parameter);
        $order->getConnection()->commit();

        $response = [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'vip' => $this->user->vip,
            'balance' => $this->user->balance,
            'coupons' => $this->coupons($order),
        ];

        
        if(is_array($result)) {
            $response = array_merge($result, $response);
        }

        return $response;
    }

    /**
     * 在订单保存之前（对订单进行一些字段赋值等）
     * @param  Orders    $order     [description]
     * @param  Request   $request   [description]
     * @param  Parameter $parameter [description]
     * @return [type]               [description]
     */
    abstract protected function create_order_before(Orders $order, Request $request, Parameter $parameter);

    /**
     * 在订单保存之后，如果返回数组则合并返回给客户端
     * @param  Orders    $order     [description]
     * @param  Request   $request   [description]
     * @param  Parameter $parameter [description]
     * @return [type]               [description]
     */
    protected function create_order_after(Orders $order, Request $request, Parameter $parameter) {

    }
}