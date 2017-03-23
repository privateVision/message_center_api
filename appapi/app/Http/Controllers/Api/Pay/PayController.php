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

        $fee = $order->fee;
        $order->paymentMethod = static::PayTypeText;
        $order->save();

        // 使用储值卡或卡券
        if($vcid && $order->vid < 100) {
            $type = substr($vcid, 0, 1);
            $vcid = substr($vcid, 1);

            // 储值卡
            if(static::EnableStoreCard && $type === '1') {
                $ucusersVC = UcusersVC::where('ucid', $ucid)->where('vcid', $vcid)->first();
                if($ucusersVC) {
                    $balance = $ucusersVC->balance;
                    $use_fee = min($fee, $balance);

                    $ordersExt = new OrdersExt;
                    $ordersExt->oid = $order->id;
                    $ordersExt->vcid = $vcid;
                    $ordersExt->fee = $use_fee;
                    $ordersExt->save();

                    $fee = $fee - $use_fee;
                }
            // 优惠券
            } elseif(static::EnableCoupon && $type === '2') {
                // todo: 读取卡券金额
                $balance = 0;
                $use_fee = min($fee, $balance);
                $ordersExt = new OrdersExt;
                $ordersExt->oid = $order->id;
                $ordersExt->vcid = intval($vcid) + 10000000;
                $ordersExt->fee = $use_fee;
                $ordersExt->save();

                $fee = $fee - $use_fee;
            }
        }

        // 使用余额
        if(static::EnableBalance && $order->vid < 100 && $fee > 0 && $this->user->balance > 0) {
            $use_fee = min($fee, $this->user->balance);
            
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
        }

        $data['real_fee'] = $fee;

        return $data;
    }

    /**
     * 订单处理函数
     * @param  Request   $request   [description]
     * @param  Parameter $parameter [description]
     * @param  [type]    $order     [description]
     * @param  [type]    $real_fee  [description]
     * @return [type]               [description]
     */
    abstract public function handle(Request $request, Parameter $parameter, Orders $order, $real_fee);
}