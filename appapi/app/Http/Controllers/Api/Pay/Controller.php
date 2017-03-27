<?php
namespace App\Http\Controllers\Api\Pay;

use App\Http\Controllers\Api\AuthController as BaseController;
use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\MongoDB\UserMessage;
use App\Model\MongoDB\UsersMessage;

class Controller extends BaseController {

    public function coupons(Orders $order) {
        $ucid = 100000183;//$this->user->ucid;
        // 储值卡
        $coupon_1 = [];
        $ucusersVC = UcusersVC::where('ucid', $ucid)->get();
        foreach($ucusersVC as $v) {
            if($v->balance <= 0) continue;

            $virtualCurrencies = VirtualCurrencies::from_cache($v->vcid);

            if($virtualCurrencies && $this->check_1($order, $virtualCurrencies)) {
                $fee = intval($v->balance * 100);
                $coupon_1[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 1, 'fee' => $fee, 'id' => $virtualCurrencies->vcid])),
                    'fee' => $fee,
                    'name' => $virtualCurrencies->vcname,
                ];
            }
        }

        // 优惠券
        $coupon_2 = [];
        $user_coupon = UserMessage::where('ucid', $ucid)->where('type', 'coupon')->get();
        foreach($user_coupon as $v) {
            $coupon = UsersMessage::where('type', 'coupon')->where('mysql_id', $v->mysql_id)->first();

            if($coupon && $this->check_2($order, $coupon)) {
                $fee = intval($coupon->money);
                $coupon_2[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 2, 'fee' => $fee, 'id' => $coupon->mysql_id])),
                    'fee' => $fee,
                    'name' => $coupon->name,
                ];
            }
        }

        return array_merge($coupon_1, $coupon_2);
    }

    public function check_1(Orders $order, $virtualCurrencies) {
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

    public function check_2(Orders $order, $coupon) {
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