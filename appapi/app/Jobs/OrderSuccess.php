<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Queue;
use App\Redis;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\UcuserTotalPay;

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
        if(!$order || $order->status != Orders::Status_WaitPay) {
            return;
        }

        // 互斥锁， 防止多次操作
        $rediskey = sprintf(Redis::KSTR_ORDER_SUCCESS_LOCK, $this->order_id);
        if(!Redis::setnx($rediskey, '1')) return $this->release(5);
        Redis::expire($rediskey, 60);

        try {
            $order->getConnection()->beginTransaction();

            if($order->user) {
                $user = $order->user;
                $orderExt = $order->ordersExt;

                // 扣除代金道具
                if($orderExt) {
                    $fail = function() use($order, $user, $rediskey) {
                        $order->getConnection()->rollback();
                        $order->status = Orders::Status_Success;
                        $order->save();
                        Redis::del($rediskey);
                    };

                    foreach($orderExt as $k => $v) {
                        if($v->vcid == 0 && $v->fee > 0) { // 处理代币
                            if($user->balance < $v->fee) {
                                log_error("balanceInsufficient", ['vcid' => $v->vcid, 'order_id' => $this->order_id, 'fee' => $v->fee, 'ucid' => $user->ucid, 'balance' => $user->balance]);
                                return $fail();
                            } else {
                                $user->decrement('balance', $v->fee);
                            }
                        }
                    }
                }

                if($order->vid == env('APP_SELF_ID')) { // 买平台币
                    $user->increment('balance', $order->fee); // 原子操作很重要
                    $user->save();
                }
                
                $ucuser_total_pay = $user->ucuser_total_pay;
                if(!$ucuser_total_pay) {
                    $ucuser_total_pay = new UcuserTotalPay();
                    $ucuser_total_pay->ucid = $user->ucid;
                    $ucuser_total_pay->pay_count = 1;
                    $ucuser_total_pay->pay_total = $order->fee;
                    $ucuser_total_pay->pay_fee = $order->real_fee();
                    $ucuser_total_pay->save();
                } else {
                    $ucuser_total_pay->increment('pay_count', 1);
                    $ucuser_total_pay->increment('pay_total', $order->fee);
                    $ucuser_total_pay->increment('pay_fee', $order->real_fee());
                    $ucuser_total_pay->save();
                }
            }

            $order->status = Orders::Status_Success;
            $order->save();

            // 非平台币，加入通知发货队列
            if($order->vid != env('APP_SELF_ID')) {
                Queue::push(new OrderNotify($this->order_id));
            }

            $order->getConnection()->commit();
        } catch(\Exception $e) {
            log_error('OrderSuccess', $e->getMessage());

            $order->getConnection()->rollback();
            $this->release(5);
        }

        Redis::del($rediskey);
    }
}