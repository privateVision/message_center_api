<?php
/**
order 订单ID或订单对象
order_extend
is_success
message
 */?><!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<head><title></title></head>

<body>
<script type="text/javascript">
<?php
if(!is_object($order)) {
    $order = \App\Model\Orders::find($order);
    $order_extend = \App\Model\OrderExtend::where('oid', $order->id)->first();
}

if($is_success) {
    $message = trans('messages.pay_success', ['name' => $order ? $order->subject : '']);
} else {
    $message = trans('messages.pay_fail', ['name' => $order ? $order->subject : '', 'message' => $message]);
}

if($order_extend && $order_extend->callback) {
    $reqdata['is_success'] = $is_success ? 1 : 0;
    $reqdata['message'] = $message;

    if($order) {
        $reqdata['open_id'] = $order->cp_uid ? $order->cp_uid : $order->ucid;
        $reqdata['body'] = $order->body;
        $reqdata['subject'] = $order->subject;
        $reqdata['fee'] = sprintf('%.2f',$order->fee);
        $reqdata['vid'] = $order->vid;
        $reqdata['sn'] = $order->sn;
        $reqdata['vorder_id'] = $order->vorderid;
        $reqdata['create_time'] = strval($order->createTime);
        $reqdata['version'] = '4.0';
    }

    if(preg_match('/^https*:/', $order_extend->callback)) {
        if(strpos($order_extend->callback, '?') === false) {
            $baseurl = $order_extend->callback . '?';
        } else {
            $baseurl = $order_extend->callback . '&';
        }

        echo sprintf('window.location.href="%s%s"', $baseurl, http_build_query($reqdata));
    } else {
        unset($reqdata['is_success'], $reqdata['message']);
        echo sprintf('%s(%s, "%s", "%s")', $order_extend->callback, $is_success ? 'true' : 'false', addcslashes($message, "\r\n\""), addcslashes(json_encode($reqdata), '"'));
    }
}
?>
</script>
<div style="padding: 30px;text-align: center;"><?=$message?></div>
</body>
</html>