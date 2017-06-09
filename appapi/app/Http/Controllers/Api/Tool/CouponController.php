<?php

namespace App\Http\Controllers\Api\Tool;

use App\Model\ZyCouponLog;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function GetCouponUsedAction()
    {
        $couponId = $this->parameter->tough('coupon_id');

        $usedNum = ZyCouponLog::where('is_used', '=', 1)->where('coupon_id', '=', $couponId)->count();
        $total = ZyCouponLog::where('coupon_id', '=', $couponId)->count();
        return [
            'used_num'=>$usedNum,
            'total' => $total
        ];
    }
}
