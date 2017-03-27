<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Queue;
use App\Redis;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\UcuserTotalPay;
use App\Model\User;

class OrderSuccess extends Job
{
    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    public function handle()
    {
        $order = Orders::find($this->order_id);
        if(!$order || $order->status != Orders::Status_WaitPay) return;

        $rediskey = sprintf('ol_%s', $this->order_id);
        Redis::mutex_lock($rediskey, function() use($order) { // 互斥锁， 防止多次操作
            $order->getConnection()->beginTransaction();

            $user = User::from_cache($order->ucid);

            $real_fee = 0;
            do {
                if(!$user) break;

                $orderExt = $order->ordersExt;
                if(!$orderExt) break;

                foreach($orderExt as $k => $v) {
                    $fee = intval($v->fee * 100);
                    $balance = intval($user->balance * 100);
                    if($fee <= 0) continue;

                    if($v->vcid > 1490587069) { // vcid > 1490587069：优惠券

                    } elseif($v->vcid > 0) { // vcid > 0：储值卡

                    } elseif($v->vcid == 0) { // vcid == 0：F币
                        if($balance < $fee) {
                            log_error("balanceError", ['text' => 'F币不足以抵扣订单', 'order_id' => $this->order_id, 'fee' => $fee, 'ucid' => $user->ucid, 'balance' => $balance]);
                            $is_s = false;
                        } else {
                            $user->decrement('balance', $fee / 100);
                        }
                    }
                }

                // F币或卡券不足，导致未扣除成功
                if(!$is_s) {
                    $order->status = Orders::Status_Success;
                    $order->save();
                    $order->getConnection()->commit();
                    return;
                }

                // 购买F币
                if($order->is_f()) {
                    $user->increment('balance', $order->fee); // 原子操作很重要
                    $user->save();
                }

                $ucuser_total_pay = UcuserTotalPay::from_cache($user->ucid);
                if(!$ucuser_total_pay) {
                    $ucuser_total_pay = new UcuserTotalPay();
                    $ucuser_total_pay->ucid = $user->ucid;
                    $ucuser_total_pay->pay_count = 1;
                    $ucuser_total_pay->pay_total = $order->fee;
                    $ucuser_total_pay->pay_fee = $real_fee / 100;
                    $ucuser_total_pay->save();
                } else {
                    $ucuser_total_pay->increment('pay_count', 1);
                    $ucuser_total_pay->increment('pay_total', $order->fee);
                    $ucuser_total_pay->increment('pay_fee', $real_fee / 100;
                    $ucuser_total_pay->save();
                }
            } while(false);

            $order->status = Orders::Status_Success;
            $order->save();

            // 非F币，加入通知发货队列
            if(!$order->is_f()) {
                Queue::push(new OrderNotify($this->order_id));
            }

            $order->getConnection()->commit();
        });
    }
}