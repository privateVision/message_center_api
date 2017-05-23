<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class YingYongBaoController extends Controller
{
    //
    protected function getData(Request $request)
    {
        // TODO: Implement getData() method.
    }

    protected function getOrderNo($data)
    {
        // TODO: Implement getOrderNo() method.
    }

    protected function getTradeOrderNo($data, $order)
    {
        // TODO: Implement getTradeOrderNo() method.
    }

    protected function verifySign($data, $order)
    {
        // TODO: Implement verifySign() method.
    }

    protected function handler($data, $order, $order_extend)
    {
        // TODO: Implement handler() method.
    }

    protected function onComplete($data, $order, $isSuccess)
    {
        // TODO: Implement onComplete() method.
    }
}
