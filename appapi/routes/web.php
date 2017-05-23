<?php
Route::get('/test/', function() {
    //$view = new Illuminate\View\View();
    //$view->getData();
    $data = [
        'callback' => '$order_extend->callback',
        'is_success' => '$is_success',
        'message' => '$message ? $message : $response',
        'openid' => '$order->cp_uid ? $order->cp_uid : $order->ucid',
        'order_no' => '$order->sn',
        'trade_order_no' => '$order->vorderid',
    ];

    var_dump(view('pay_callback/cb', $data) instanceof Illuminate\View\View);
});