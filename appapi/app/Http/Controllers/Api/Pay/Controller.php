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
        $ucid = $this->user->ucid;
        // 储值卡
        $coupon_1 = [];
        $ucusersVC = UcusersVC::where('ucid', $ucid)->get();
        foreach($ucusersVC as $v) {
            if($v->balance <= 0) continue;

            $virtualCurrencies = VirtualCurrencies::from_cache($v->vcid);

            if($virtualCurrencies && $this->check_1($virtualCurrencies)) {
                $coupon_1[] = [
                    'id' => sprintf('%d%d', 1, $virtualCurrencies->vcid),
                    'fee' => $v->balance,
                    'name' => $virtualCurrencies->vcname,
                ];
            }
        }

        // 优惠券
        $coupon_2 = [];
        $user_coupon = UserMessage::where('ucid', $ucid)->where('type', 'coupon')->get();
        foreach($user_coupon as $v) {
            $coupon = UsersMessage::where('type', 'coupon')->where('mysql_id', $v->mysql_id)->first();

            if($coupon && $this->check_2($coupon, $order->fee)) {
                $coupon_2[] = [
                    'id' => sprintf('%d%d', 2, $coupon->mysql_id),
                    'fee' => intval($coupon->money / 100),
                    'name' => $coupon->name,
                ];
            }
        }

        return array_merge($coupon_1, $coupon_2);
    }

    public function check_1($virtualCurrencies) {
        if($virtualCurrencies->lockApp != $this->procedure->pid) return false;
        if($virtualCurrencies->untimed) {
            $s = strtotime($virtualCurrencies->startTime);
            $e = strtotime($virtualCurrencies->endTime);
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        return true;
    }

    public function check_2($coupon, $fee) {
        $full = intval($coupon->full / 100);
    }

}