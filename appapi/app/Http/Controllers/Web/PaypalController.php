<?php
namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;
use App\Model\ExchangeRate;

class PaypalController extends \App\Controller {

    public function PaymentAction(Request $request) {
        $sign = $request->get('sign');

        $data = decrypt3des($sign);
        if(!$data) return;

        $data = json_decode($data, true);
        if(!$data) return;

        log_info('paypal:payment:sign', $data, $sign);

        $amount = exchange_rate($data['amount'], 'USD');
        if(!$amount || $amount === '0.00') {
            // 正常情况不会走到这里，非正常情况就是不怀好意的人，还给他个提示？啊呸！！！
            return;
        }

        $return_url = url('web/paypal/return?sign=' . urlencode($sign));
        $calcel_url = url('web/paypal/cancel?sign=' . urlencode($sign));
        $notify_url = url('pay_callback/paypal');

$html = <<<HTML
<!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<head>
<title></title>
</head>

<body>
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" id="buyform">
<input name="cmd" type="hidden" value="_xclick">
<input name="business" type="hidden" value="{$data['business']}">
<input name="item_name" type="hidden" value="{$data['product_name']}">
<input name="item_number" type="hidden" value="{$data['product_name']}">
<input name="amount" type="hidden" value="{$amount}">
<input name="quantity" type="hidden" value="1">
<input name="no_shipping" type="hidden" value="1">
<input name="currency_code" type="hidden" value="USD">
<input name="invoice" type="hidden" value="{$data['order_no']}">
<input type="hidden" name="return" value="{$return_url}">
<input type="hidden" name="cancel_return" value="{$calcel_url}">
<input type="hidden" name="notify_url" value="{$notify_url}">
</form>
<script type="text/javascript">
window.onload = function() {
    document.getElementById('buyform').submit();
}
</script>
</body>
</html>
HTML;

        return response($html);
    }

    public function ReturnAction(Request $request) {
        $sign = $request->get('sign');

        $data = decrypt3des($sign);
        if(!$data) return;

        $data = json_decode($data, true);
        if(!$data) return;

        log_info('paypal:payment:sign', $data, $sign);

        return view('pay_callback/callback', ['order' => $data['order_id'], 'is_success' => true]);
    }

    public function CancelAction(Request $request) {
        $sign = $request->get('sign');

        $data = decrypt3des($sign);
        if(!$data) return;

        $data = json_decode($data, true);
        if(!$data) return;

        log_info('paypal:payment:sign', $data, $sign);

        return view('pay_callback/callback', ['order' => $data['order_id'], 'is_success' => false, 'message' => trans('messages.pay_cancel')]);
    }

}