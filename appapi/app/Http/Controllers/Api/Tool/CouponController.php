<?php

namespace App\Http\Controllers\Api\Tool;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function GetCouponUsedAction()
    {
        $couponId = $this->parameter->tough('coupon_id');

        $usedNum = DB::table('zy_coupon_log')
                    ->where('is_used', '=', 1)
                    ->where('coupon_id', '=', $couponId)
                    ->count();
        $total = DB::table('zy_coupon_log')
                    ->where('coupon_id', '=', $couponId)
                    ->count();
        return [
            'used_num'=>$usedNum,
            'total' => $total
        ];
    }
}
