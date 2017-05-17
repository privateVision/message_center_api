<?php

namespace App\Http\Controllers\Api\Pay;

use App\Model\Orders;
use Illuminate\Http\Request;

class YingYongBaoController extends Controller
{
    use RequestAction;

    const PayType = '-8';
    const PayTypeText = '应用宝';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    /**
     * 订单处理函数，重写该函数实现不同的支付方式
     * @param  Orders $order [description]
     * @param  int $real_fee 实际支付金额，单位：分
     * @return [type]               [description]
     */
    protected function payHandle(Orders $order, $real_fee)
    {

    }
}
