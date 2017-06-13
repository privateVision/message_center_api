<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class LeshiController extends Controller
{
    //
    /**
     * 获取回调的所有数据
     * @param  Request $request
     */
    protected function getData ( Request $request )
    {
        return $_GET;
    }

    /**
     * 获取我方订单号
     * @param mixed $data getData方法返回的数据
     */
    protected function getOrderNo ( $data )
    {
        return $data['cooperator_order_no'];
    }

    /**
     * 获取第三方订单号（微信，支付宝等）
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function getTradeOrderNo ( $data , $order, $order_extend )
    {
        return $data['out_trade_no'];
    }

    /**
     * 验证签名
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function verifySign ( $data , $order, $order_extend )
    {
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();

        if(!$proceduresExtend)return false;

        unset($data['sign']);
        ksort($data);

        $str = '';

        foreach ($data as $k=>$v){
            $str .= $k.'='.$v.'&';
        }

        $sign = md5($str.'key='.$proceduresExtend->third_appkey);

        return $sign==$data['sign'];
    }

    /**
     * 订单特殊处理逻辑判断，如果返回false则视为订单支付失败
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function handler ( $data , $order , $order_extend )
    {
        return true;
    }

    /**
     * 订单完成后的回应，返回值将直接输出给回调方
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @param  boolean $is_success 订单是否处理成功
     */
    protected function onComplete ( $data , $order, $order_extend , $is_success, $message = null)
    {
        return $is_success?'success':'fail';
    }
}
