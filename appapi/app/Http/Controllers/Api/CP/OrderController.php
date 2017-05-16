<?php
namespace App\Http\Controllers\Api\CP;

use App\Exceptions\ApiException;
use App\Model\Orders;

class OrderController extends Controller {

    public function GetOrderInfoAction() {
        $open_id = $this->parameter->tough('open_id');
        $sn = $this->parameter->tough('sn');

        $order = Orders::where('cp_uid', $open_id)->where('sn', $sn)->first();
        if(!$order) {
            throw new ApiException(1000, '订单不存在');
        }

        $data = [];
        $data['open_id'] = $order->cp_uid;
        $data['vorderer_id'] = $order->vorderid;
        $data['sn'] = $order->sn;
        $data['app_id'] = $order->vid;
        $data['fee'] = $order->fee;
        $data['body'] = $order->body;
        $data['create_time'] = strval($order->createTime);

        if($order->status == Orders::Status_Success) {
            $data['order_status'] = 'pay_success';
        } elseif($order->status == Orders::Status_NotifySuccess) {
            $data['order_status'] = 'notify_success';
        } else {
            $data['order_status'] = 'not_pay';
        }

        return $data;
    }
}