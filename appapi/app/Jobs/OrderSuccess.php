<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Queue;
use App\Redis;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;
use App\Model\UcuserTotalPay;
use App\Model\Ucuser;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCouponLog;
use App\Model\ZyCoupon;
use App\Model\TotalFeePerUser;

class OrderSuccess extends Job
{
    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    public function handle() {
        $order = Orders::from_cache($this->order_id);
        if(!$order || $order->status != Orders::Status_WaitPay) {
            log_debug('OrderSuccessError', ['order_id' => $this->order_id], '订单状态已完成，无需处理');
            return;
        }

        $rediskey = sprintf('order_lock_%s', $this->order_id);
        
        Redis::mutex_lock($rediskey, function() use($order) { // 互斥锁， 防止多次操作
            try {
                $order->getConnection()->beginTransaction();

                $user = Ucuser::from_cache($order->ucid);

                do {
                    if(!$user) break;

                    $is_s = true;
                    $orderExt = $order->ordersExt;
                    foreach($orderExt as $k => $v) {
                        $fee = intval($v->fee * 100);
                        if($fee <= 0) continue;

                        if($v->vcid > 100000000) { // vcid > 100000000：优惠券
                            $coupon = ZyCouponLog::find($v->vcid);
                            if($coupon && !$coupon->is_used) {
                                $coupon->is_used = true;
                                $coupon->save();
                                continue;
                            }

                            log_error("orderFail", ['order_id' => $this->order_id, 'fee' => $fee, 'ucid' => $user->ucid, 'vcid' => $v->vcid], '优惠券无效');
                            $is_s = false;
                        } elseif($v->vcid > 0) { // vcid > 0：储值卡
                            $ucusersvc = UcusersVC::where('ucid', $order->ucid)->where('vcid', $v->vcid)->first(); // todo: 联合主键，ORM不支持
                            if(!$ucusersvc || intval($ucusersvc->balance * 100) < $fee) {
                                log_error("orderFail", ['order_id' => $this->order_id, 'fee' => $fee, 'ucid' => $user->ucid, 'vcid' => $v->vcid], '储值卡余额不足以抵扣订单');
                                $is_s = false;
                            } else {
                                 UcusersVC::where('ucid', $order->ucid)->where('vcid', $v->vcid)->decrement('balance', $fee / 100); // todo: 联合主键，ORM不支持
                            }
                        } elseif($v->vcid == 0) { // vcid == 0：F币
                            if(intval($user->balance * 100) < $fee) {
                                log_error("orderFail", ['order_id' => $this->order_id, 'fee' => $fee, 'ucid' => $user->ucid, 'balance' => intval($user->balance)], 'F币不足以抵扣订单');
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
                } while(false);

                // ucuser_total_pay
                $ucuser_total_pay = UcuserTotalPay::from_cache($user->ucid);
                if(!$ucuser_total_pay) {
                    $ucuser_total_pay = new UcuserTotalPay();
                    $ucuser_total_pay->ucid = $user->ucid;
                    $ucuser_total_pay->pay_count = 1;
                    $ucuser_total_pay->pay_total = $order->fee;
                    $ucuser_total_pay->pay_fee = $order->real_fee / 100;
                    $ucuser_total_pay->save();
                } else {
                    $ucuser_total_pay->increment('pay_count', 1);
                    $ucuser_total_pay->increment('pay_total', $order->fee);
                    $ucuser_total_pay->increment('pay_fee', $order->real_fee / 100);
                }

                // total_fee_per_user
                $total_fee_per_user = TotalFeePerUser::where('ucid', $user->ucid)->where('pid', $order->vid)->first();
                if(!$total_fee_per_user) {
                    $total_fee_per_user = new TotalFeePerUser();
                    $total_fee_per_user->ucid = $user->ucid;
                    $total_fee_per_user->pid = $order->vid;
                    $total_fee_per_user->oid = $order->id;
                    $total_fee_per_user->lastpay_pid = $order->vid;
                    $total_fee_per_user->lastpay_time = $order->createTime;
                    $total_fee_per_user->playCount = 1;
                    $total_fee_per_user->total_fee = $order->real_fee / 100;
                    $total_fee_per_user->save();
                } else {
                    $total_fee_per_user->increment('playCount', 1);
                    $total_fee_per_user->increment('total_fee', $order->real_fee / 100);
                }

                if($order->is_f()) {
                    log_debug('OrderSuccess', $order->toArray(), '购买F币');
                    $user->increment('balance', $order->fee); // 原子操作很重要
                    $order->status = Orders::Status_NotifySuccess;
                    $order->save();
                } else {
                    $order->status = Orders::Status_Success;
                    $order->save();
                    Queue::push(new OrderNotify($this->order_id));
                }

                $order->getConnection()->commit();
            } catch(\Exception $e) {
                log_error('OrderSuccessError', $e->getMessage(), $e->getMessage());
                $this->release(5);
            }
        }, function() {
            $this->release(5);
        });
    }
}