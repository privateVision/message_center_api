<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\UcuserInfo;
use App\Model\Orders;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCouponLog;
use App\Model\ZyCoupon;

trait CreateOrderAction {

    public function NewAction() {
        $pid = $this->procedure->pid;

        $order = new Orders;
        $order->getConnection()->beginTransaction();

        $order->ucid = $this->user->ucid;
        $order->uid = $this->user->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->createIP = $this->request->ip();
        $order->status = Orders::Status_WaitPay;
        $order->paymentMethod = '';
        $order->cp_uid = $this->session->cp_uid;
        $order->user_sub_id = $this->session->user_sub_id;
        $order->user_sub_name = $this->session->user_sub_name;
        $this->onCreateOrder($order);
        $order->real_fee = $order->fee;
        $order->save();

        $order->getConnection()->commit();

        $order_is_first = $order->is_first();
        
        // 储值卡，优惠券
        $list = [];

        $result = UcusersVC::where('ucid', $this->user->ucid)->get();
        foreach($result as $v) {
            $fee = $v->balance * 100;
            if(!$fee) continue;

            $rule = VirtualCurrencies::from_cache($v->vcid);
            if(!$rule) continue;

            $e = $rule->is_valid($pid);
            if($e === false) continue;

            $list[] = [
                'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 1, 'fee' => $fee, 'id' => $v->vcid, 'e' => $e])),
                'fee' => $fee,
                'name' => $rule->vcname,
            ];
        }

        $result = ZyCouponLog::where('ucid', $this->user->ucid)->where('is_used', false)->whereIn('pid', [0, $pid])->get();
        foreach($result as $v) {
            $rule = ZyCoupon::from_cache($v->coupon_id);
            if(!$rule) continue;

            $fee = $rule->money;
            if(!$fee) continue;
            
            $e = $rule->is_valid($pid, $order->fee, $order_is_first);
            if($e === false) continue;

            $list[] = [
                'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 2, 'fee' => $fee, 'id' => $v->id, 'e' => $e])),
                'fee' => $fee,
                'name' => $rule->name,
            ];
        }

        $user_info = UcuserInfo::from_cache($this->user->ucid);

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'fee' => $order->fee,
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'balance' => $this->user->balance,
            'coupons' => $list,
        ];
    }

    /**
     * 在订单保存之前（对订单进行一些字段赋值等）
     * @param  Orders    $order     [description]
     * @return [type]               [description]
     */
    abstract protected function onCreateOrder(Orders $order);
}
