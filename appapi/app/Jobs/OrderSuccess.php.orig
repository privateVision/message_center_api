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
        // 互斥锁， 防止多次操作
        $lock_key = 'order_success_lock_' . $this->order_id;
        $lock = Redis::setnx($lock_key, '1');
        if(!$lock) $this->release(5);
        // 程序运行完毕解锁，防止死锁
        register_shutdown_function(function() use($lock_key) {
            Redis::del($lock_key);
        });

        $order = Orders::find($this->order_id);
        if(!$order) return;

        // 订单处理过了，不再处理
        if($order->status == Orders::Status_Success) return;

        try {
            $order->getConnection()->beginTransaction();

            // 扣除代金道具
            $orderExt = $this->ordersExt;
            if($orderExt) {
                foreach($ordersExt as $k => $v) {
                    if($v->vcid == 0) {
                        
                    }
                }
            }

            if($order->ucusers) {
                if($order->vid == 2) { // 买平台币
                    $order->ucusers->increment('balance', $order->fee); // 原子操作很重要
                    $order->ucusers->save();
                }
                
                if(!$order->ucusers->ucuser_total_pay) {
                    $order->ucusers->ucuser_total_pay = new UcuserTotalPay();
                    $order->ucusers->ucuser_total_pay->ucid = $order->ucusers->ucid;
                    $order->ucusers->ucuser_total_pay->pay_count = 1;
                    $order->ucusers->ucuser_total_pay->pay_total = $order->fee;
                    $order->ucusers->ucuser_total_pay->pay_fee = $order->fee;
                    $order->ucusers->ucuser_total_pay->save();
                } else {
                    $order->ucusers->ucuser_total_pay->increment('pay_count', 1);
                    $order->ucusers->ucuser_total_pay->increment('pay_total', $order->fee);
                    $order->ucusers->ucuser_total_pay->increment('pay_fee', $order->real_fee());
                    $order->ucusers->ucuser_total_pay->save();
                }
            }

            $order->status = Orders::Status_Success;
            $order->save();

            // 非平台币，加入通知发货队列
            if($order->vid != 2) Queue::push(new OrderNotiry($this->order_id));

            $order->getConnection()->commit();
        } catch(\Exception $e) {
            $order->getConnection()->rollback();
            return $this->release(1);
        }
    }
}