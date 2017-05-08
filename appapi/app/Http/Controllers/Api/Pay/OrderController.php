<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;
use App\Model\OrderExtend;
use App\Model\UcuserInfo;

class OrderController extends Controller {

    use CreateOrderAction;

    protected function onCreateOrder(Orders $order) {
        $fee = $this->parameter->tough('fee');
        $body = $this->parameter->tough('body');
        $subject = $this->parameter->tough('subject');
        $vorderid = $this->parameter->tough('vorderid');
        $notify_url = $this->parameter->tough('notify_url');

        if(env('realname')) {
            $user_info = UcuserInfo::from_cache($this->user->ucid);
            if(!$user_info || !$user_info->card_no) {
                throw new ApiException(ApiException::NotRealName, '帐号未实名制，无法支付，请先实名后再操作');
            }
        }

        $order->notify_url = $notify_url;
        $order->vorderid = $vorderid;
        $order->fee = $fee;
        $order->subject = $subject;
        $order->body = $body;
    }

    public function InfoAction() {
        $sn = $this->parameter->tough('order_id');

        $order = Orders::from_cache_sn($sn);

        if($order) {
            return [
                'status' => $order->status
            ];
        }

        return [];
    }
}