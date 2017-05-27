<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;

trait RequestAction {

    public function RequestAction() {
        $order_id = $this->parameter->tough('order_id');
        $balance = $this->parameter->get('balance');
        $vcid = $this->parameter->get('vcid');
        $pay_type = $this->parameter->get('pay_type', 0);

        $order = Orders::from_cache_sn($order_id);
        if(!$order) {
            throw new ApiException(ApiException::Remind, trans('messages.order_not_exists'));
        }

        if($order->status != Orders::Status_WaitPay) {
            throw new ApiException(ApiException::Remind, trans('messages.order_already_success'));
        }

        $order_extend = OrderExtend::find($order->id);

        $order->getConnection()->beginTransaction();

        // XXX 同一笔订单被多次支付利用(清除旧数据)
        OrdersExt::where('oid', $order->id)->delete();

        // XXX 4.1和以上版本直接判断$order_extend->is_f()即可
        $is_f = ($order_extend && $order_extend->is_f()) || $order->is_f();
        $fee = $order->fee * 100;

        // 使用储值卡或卡券
        do {
            if(!$vcid || $is_f)  break;

            $vcinfo = json_decode(decrypt3des($vcid), true);
            if(!$vcinfo)  break;

            if($vcinfo['oid'] != $order->id) break;

            // 储值卡
            if($vcinfo['type'] == 1) {
                if($vcinfo['e'] > 0 && $vcinfo['e'] < time()) {
                    throw new ApiException(ApiException::Remind, trans('messages.coupon_expire'));
                }

                $use_fee = min($fee, $vcinfo['fee']);

                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = $vcinfo['id'];
                $ordersExt->fee = $use_fee / 100;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            // 优惠券
            } elseif($vcinfo['type'] == 2) {
                if($vcinfo['e'] > 0 && $vcinfo['e'] < time()) {
                    throw new ApiException(ApiException::Remind, trans('messages.coupon_expire'));
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
        if($balance > 0 && !$is_f && $fee > 0 && $this->user->balance > 0) {
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
            $ordersExt->vcid = static::PayMethod;
            $ordersExt->fee = $fee / 100;
            $ordersExt->save();
        } else {
            // XXX 不用支付，直接发货
            order_success($order->id);
        }

        // 获取配置传给子类
        $config = config('common.payconfig.'.static::PayText);
        if(!$config) {
            // 可以没有配置
            // throw new ApiException(ApiException::Remind, trans('messages.not_payconfig'));
        }

        $data = [
            'pay_type' => $pay_type,
            'pay_method' => static::PayText,
            'order_id' => $order_id,
            'real_fee' => $fee,
        ];

        if($pay_type == 0) {
            // XXX 为了兼容旧的代码
            // $data['data'] = $this->getData($config, $order, $order_extend, $fee);
            $data = array_merge($data, $this->getData($config, $order, $order_extend, $fee));
        } elseif($pay_type == 1) {
            $data['url_scheme'] = $this->getUrlScheme($config, $order, $order_extend, $fee);
        } elseif($pay_type == 2) {
            $data['url'] = $this->getUrl($config, $order, $order_extend, $fee);
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.not_allow_pay_type'));
        }

        $order->paymentMethod = static::PayTypeText;
        $order->real_fee = $fee;
        $order->save();

        $order_extend->pay_method = static::PayMethod;
        $order_extend->pay_type = $pay_type;
        $order_extend->real_fee = $fee;
        $order_extend->callback = $this->parameter->get('callback', '');
        $order_extend->asyncSave();

        $order->getConnection()->commit();

        return $data;
    }

    /**
     * 当客户端集成SDK发起支付时返回数据
     * @param Orders $order
     * @param $real_fee
     * @return mixed
     */
    protected function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        throw new ApiException(ApiException::Remind, trans('messages.not_allow_pay_type'));
    }

    /**
     * 当客户端通过webview发起支付时返回url
     * @param Orders $order
     * @param $real_fee
     * @return mixed
     */
    protected function getUrl($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        throw new ApiException(ApiException::Remind, trans('messages.not_allow_pay_type'));
    }

    /**
     * 当客户端通过url scheme发起支付时返回urlscheme
     * @param Orders $order
     * @param $real_fee
     * @return mixed
     */
    protected function getUrlScheme($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        throw new ApiException(ApiException::Remind, trans('messages.not_allow_pay_type'));
    }
}