<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;

trait RequestAction {

    public function RequestAction() {
        $order_id = $this->parameter->tough('order_id');
        $balance = $this->parameter->get('balance');
        $vcid = $this->parameter->get('vcid');

        $order = Orders::from_cache_sn($order_id);
        if(!$order) {
            throw new ApiException(ApiException::Remind, '订单不存在');
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, '订单已支付完成，请勿重复支付');
        }

        $order->getConnection()->beginTransaction();

        // todo: 同一笔订单被多次支付利用(清除旧数据)
        OrdersExt::where('oid', $order->id)->delete();

        $is_f = $order->is_f(); // 小于100的应用是内部应用，只能充F币
        $fee = $order->fee * 100;

        // 使用储值卡或卡券
        do {
            if(!$vcid || $is_f)  break;

            $vcinfo = json_decode(decrypt3des($vcid), true);
            if(!$vcinfo)  break;

            if($vcinfo['oid'] != $order->id) break;

            // 储值卡
            if(static::EnableStoreCard && $vcinfo['type'] == 1) {
                if($vcinfo['e'] > 0 && $vcinfo['e'] < time()) {
                    throw new ApiException(ApiException::Remind, '优惠券不可使用，已过期');
                }

                $use_fee = min($fee, $vcinfo['fee']);

                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = $vcinfo['id'];
                $ordersExt->fee = $use_fee / 100;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            // 优惠券
            } elseif(static::EnableCoupon && $vcinfo['type'] == 2) {
                if($vcinfo['e'] > 0 && $vcinfo['e'] < time()) {
                    throw new ApiException(ApiException::Remind, '优惠券不可使用，已过期');
                }

                $use_fee = min($fee, $vcinfo['fee']);

                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = $vcinfo['id'];
                $ordersExt->fee = $use_fee / 100;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            }
        } while(false);

        // 使用余额
        if(static::EnableBalance && $balance > 0 && !$is_f && $fee > 0 && $this->user->balance > 0) {
            $use_fee = min($fee, $this->user->balance * 100);
            
            $ordersExt = new OrdersExt;
            $ordersExt->oid = $order->id;
            $ordersExt->vcid = 0;
            $ordersExt->fee = $use_fee / 100;
            $ordersExt->save();

            $fee = $fee - $use_fee;
        }

        // 实际支付
        $data = [];
        if($fee > 0) {
            $ordersExt = new OrdersExt;
            $ordersExt->oid = $order->id;
            $ordersExt->vcid = static::PayType;
            $ordersExt->fee = $fee / 100;
            $ordersExt->save();

            // $data = $this->payHandle($order, $fee);
        } else {
            // order_success($order->id); // 不用支付，直接发货
        }

        $data = $this->payHandle($order, $fee);

        $order->paymentMethod = static::PayTypeText;
        $order->real_fee = $fee;
        $order->save();
        $order->getConnection()->commit();

        $data['real_fee'] = $fee;

        return $data;
    }

    /**
     * 订单处理函数，重写该函数实现不同的支付方式
     * @param  Orders    $order     [description]
     * @param  int       $real_fee  实际支付金额，单位：分
     * @return [type]               [description]
     */
    abstract protected function payHandle(Orders $order, $real_fee);
}