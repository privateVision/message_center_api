<?php
namespace App\Model;

class ZyCoupon extends Model
{
    protected $table = 'zy_coupon';
    protected $primaryKey = 'id';

    /**
     * 验证卡券是否可用
     * @param  [type]  $pid            
     * @param  [type]  $order_fee      订单总额：元
     * @param  [type]  $order_is_first 是否是首充
     * @return mixed false，不可用，0可用且不限制时间，大于0表示可用且限制的时间
     */
    public function is_valid($pid, $order_fee, $order_is_first) {
        $fee = intval($order_fee * 100);

        // 满 full 元可用
        if($this->full && $this->full > $fee) return false;

        // 首充可用
        if($this->is_first && !$order_is_first) return false;

        $e = 0;

        // 时间限制
        if($this->is_time) {
            $s = $this->start_time;
            $e = $this->end_time;
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        // 游戏限制
        if($this->game && $pid != $this->game) {
            return false;
        }

        return $e;
    }
}