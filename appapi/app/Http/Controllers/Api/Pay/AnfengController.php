<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class AnfengController extends PayController {

    const PayType = '-1';
    const PayTypeText = 'F币或卡券直接支付';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function handle(Request $request, Parameter $parameter, Orders $order, $real_fee) {
        if($real_fee > 0) {
            throw new ApiException(ApiException::Remind, '不能使用余额直接抵扣');
        }
        
        order_success($order->id);

        return ['result' => true];
    }
}