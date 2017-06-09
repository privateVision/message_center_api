<?php

namespace App\Http\Controllers\Api\Tool;

use Illuminate\Http\Request;
use App\Parameter;

class CouponController extends Controller
{
    public function GetCouponUsedAction()
    {
        $couponId = $this->parameter->tough('coupon_id');


    }
}
