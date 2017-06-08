<?php
namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;

class PaypalController extends \App\Controller {

    public function PaymentAction(Request $request) {
        return "";
        $sign = $request->get('sign');
        $data = decrypt3des($sign);
        if(!$data) return;

        $data = json_decode($data, true);
        if(!$data) return;

        $return_url = url('web/paypal/return');
        $calcel_url = url('web/paypal/calcel');
        $notify_url = url('web/paypal/notify');

        $html = <<<HTML
<!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<head><title></title></head>
<script type="text/javascript">
window.onload = function() {
    document.getElementById('buyform').submit();
}
</script>

<body>
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" id="buyform">
<input name="cmd" type="hidden" value="_xclick">
<input name="business" type="hidden" value="{$data['business']}">
<input name="item_name" type="hidden" value="{$data['product_name']}">
<input name="item_number" type="hidden" value="{$data['product_name']}">
<input name="amount" type="hidden" value="{$data['amount']}">
<input name="quantity" type="hidden" value="1">
<input name="no_shipping" type="hidden" value="1">
<input name="currency_code" type="hidden" value="USD">
<input name="invoice" type="hidden" value="{$data['order_no']}">
<input type="hidden" name="return" value="{$return_url}">
<input type="hidden" name="cancel_return" value="{$calcel_url}">
<input type="hidden" name="notify_url" value="{$notify_url}">
</form>
</body>
</html>
HTML;
        return $html;
    }

    public function ReturnAction(Request $request) {
        return 'return';
    }

    public function CancelAction(Request $request) {
        return 'cancel';
    }

}