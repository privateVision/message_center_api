<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\MongoDB\UserMessage;
use App\Model\MongoDB\UsersMessage;

trait CreateOrderAction {

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
        $this->createOrderBefore($order, $request, $parameter);
        $order->save();

        $order_extend = new OrderExtend;
        $order_extend->order_id = $order->id;
        $order_extend->real_fee = 0;
        $order_extend->cp_uid = $this->session->cp_uid;
        $order_extend->save();

        $result = $this->createOrderAfter($order, $request, $parameter);
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
    abstract protected function createOrderBefore(Orders $order, Request $request, Parameter $parameter);

    /**
     * 在订单保存之后，如果返回数组则合并返回给客户端
     * @param  Orders    $order     [description]
     * @param  Request   $request   [description]
     * @param  Parameter $parameter [description]
     * @return [type]               [description]
     */
    protected function createOrderAfter(Orders $order, Request $request, Parameter $parameter) {

    }

    public function coupons(Orders $order) {
        $ucid = $this->user->ucid;
        // 储值卡
        $data_1 = [];
        $ucusersVC = UcusersVC::where('ucid', $ucid)->get();
        foreach($ucusersVC as $v) {
            if($v->balance <= 0) continue;

            $virtualCurrencies = VirtualCurrencies::from_cache($v->vcid);

            if($virtualCurrencies && $this->checkStoreCard($order, $virtualCurrencies)) {
                $fee = intval($v->balance * 100);
                $data_1[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 1, 'fee' => $fee, 'id' => $virtualCurrencies->vcid])),
                    'fee' => $fee,
                    'name' => $virtualCurrencies->vcname,
                ];
            }
        }

        // 优惠券
        $data_2 = [];
        $user_coupon = UserMessage::where('ucid', $ucid)->where('type', 'coupon')->get();
        foreach($user_coupon as $v) {
            $coupon = UsersMessage::where('type', 'coupon')->where('mysql_id', $v->mysql_id)->first();

            if($coupon && $this->checkCoupon($order, $coupon)) {
                $fee = intval($coupon->money);
                $data_2[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 2, 'fee' => $fee, 'id' => $coupon->mysql_id])),
                    'fee' => $fee,
                    'name' => $coupon->name,
                ];
            }
        }

        return array_merge($data_1, $data_2);
    }

    public function checkStoreCard(Orders $order, $virtualCurrencies) {
        if($virtualCurrencies->lockApp != $this->procedure->pid) return false;
        if(!$virtualCurrencies->untimed) {
            $s = strtotime($virtualCurrencies->startTime);
            $e = strtotime($virtualCurrencies->endTime);
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        return true;
    }

    public function checkCoupon(Orders $order, $coupon) {
        $fee = $order->fee * 100;

        if($coupon->full < $fee) return false;
        if($coupon->is_first == 1 && !$order->is_first()) return false;
        if(!$coupon->is_time) {
            $s = $coupon->start_time;
            $e = $coupon->end_time;
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        if(is_array($coupon->app)) {
            foreach($coupon->app as $v) {
                if($v['apk_id'] == $order->pid) return true;
            }
        }

        return false;
    }
}