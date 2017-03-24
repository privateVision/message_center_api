<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;

abstract class PayController extends Controller {

    public function RequestAction(Request $request, Parameter $parameter) {
        $order_id = $parameter->tough('order_id');
        $balance = $parameter->get('balance');
        $vcid = $parameter->get('vcid');

        $order = Orders::where('sn', $order_id)->first();
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单状态不正确');
        }

        $discount_enable = $order->vid >= 100;
        $fee = $order->fee * 100;
        $order->paymentMethod = static::PayTypeText;
        $order->save();

        // 使用储值卡或卡券
        do {
            if(!$vcid || !$discount_enable)  break;

            $vcinfo = json_decode(decrypt3des($vcid), true);
            if(!$vcinfo)  break;

            $id = $vcinfo['id'];
            $balance = $vcinfo['fee'];
            $bind_order_id = $vcinfo['order_id'];

            if($bind_order_id != $order->id) break;

            // 储值卡
            if(static::EnableStoreCard && $vcinfo['type'] == 1) {
                $use_fee = min($fee, $balance);

                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = $vcid;
                $ordersExt->fee = $use_fee;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            // 优惠券
            } elseif(static::EnableCoupon && $vcinfo['type'] == 2) {
                $use_fee = min($fee, $balance);

                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = intval($vcid) + 10000000;
                $ordersExt->fee = $use_fee;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            }
        } while(false);

        // 使用余额
        if(static::EnableBalance && $discount_enable && $fee > 0 && $this->user->balance > 0) {
            $use_fee = min($fee, $this->user->balance * 100);
            
            $ordersExt = new OrdersExt;
            $ordersExt->oid = $order->id;
            $ordersExt->vcid = 0;
            $ordersExt->fee = $use_fee;
            $ordersExt->save();

            $fee = $fee - $use_fee;
        }

        // 实际支付
        $data = [];
        if($fee > 0) {
            $ordersExt = new OrdersExt;
            $ordersExt->oid = $order->id;
            $ordersExt->vcid = static::PayType;
            $ordersExt->fee = $fee;
            $ordersExt->save();

            $data = $this->handle($request, $parameter, $order, $fee);
        } else {
            order_success($order->id); // 不用支付，直接发货
        }

        $data['real_fee'] = $fee;

        return $data;
    }

    /**
     * 订单处理函数，重写该函数实现不同的支付方式
     * @param  Request   $request   [description]
     * @param  Parameter $parameter [description]
     * @param  Orders    $order     [description]
     * @param  int       $real_fee  实际支付金额，单位：分
     * @return [type]               [description]
     */
    abstract public function handle(Request $request, Parameter $parameter, Orders $order, $real_fee);
}