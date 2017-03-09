<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
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
        $lock_key = 'laravel_order_lock_' . $this->order_id;
        if(!Redis::setnx($lock_key, '1')) return $this->release(5);
        Redis::expire($lock_key, 60);

        try {
            $order->getConnection()->beginTransaction();

            if($order->ucusers) {
                $ucuser = $order->ucusers;
                $orderExt = $order->ordersExt;

                // 扣除代金道具
                if($orderExt) {
                    $fail = function() use($order, $ucuser, $lock_key) {
                        $order->getConnection()->rollback();
                        $order->status = Orders::Status_Success;
                        $order->save();
                        Redis::del($lock_key);
                    };

                    foreach($orderExt as $k => $v) {
                        if($v->vcid == 0 && $v->fee > 0) { // 处理代币
                            if($ucuser->balance < $v->fee) {
                                log_error("balanceInsufficient", ['vcid' => $v->vcid, 'order_id' => $this->order_id, 'fee' => $v->fee, 'ucid' => $ucuser->ucid, 'balance' => $ucuser->balance]);
                                return $fail();
                            } else {
                                $ucuser->decrement('balance', $v->fee);
                            }
                        }
                    }
                }

                if($order->vid == env('APP_SELF_ID')) { // 买平台币
                    $ucuser->increment('balance', $order->fee); // 原子操作很重要
                    $ucuser->save();
                }
                
                $ucuser_total_pay = $ucuser->ucuser_total_pay;
                if(!$ucuser_total_pay) {
                    $ucuser_total_pay = new UcuserTotalPay();
                    $ucuser_total_pay->ucid = $ucuser->ucid;
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

        Redis::del($lock_key);
    }
}