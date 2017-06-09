<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class MiController extends Controller
{
    //
    /**
     * 获取回调的所有数据
     * @param  Request $request
     */
    protected function getData(Request $request)
    {
        return $_GET;
    }

    /**
     * 获取我方订单号
     * @param mixed $data getData方法返回的数据
     */
    protected function getOrderNo($data)
    {
        return $data['cpOrderId'];
    }

    /**
     * 获取第三方订单号（微信，支付宝等）
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @return mixed
     */
    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['orderId'];
    }

    /**
     * 验证签名
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function verifySign($data, $order, $order_extend)
    {
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();

        if(!$proceduresExtend)return false;

        $params = [
            'appId' => $data['appId'],
            'cpOrderId' => $data['cpOrderId'],
            'cpUserInfo' => $data['cpUserInfo'],
            'uid' => $data['uid'],
            'orderId' => $data['orderId'],
            'orderStatus' => $data['orderStatus'],
            'payFee' => $data['payFee'],
            'productCode' => $data['productCode'],
            'productName' => $data['productName'],
            'productCount' => $data['productCount'],
            'payTime' => $data['payTime'],
            'partnerGiftConsume' => $data['partnerGiftConsume'],
        ];

        if(isset($data['orderConsumeType'])&&$data['orderConsumeType']) $params['orderConsumeType'] = $data['orderConsumeType'];

        $sign = $this->sign($params, $proceduresExtend->third_appsecret);

        return $sign==$data['signature'];

    }

    /**
     * 订单特殊处理逻辑判断，如果返回false则视为订单支付失败
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function handler($data, $order, $order_extend)
    {
        return true;
    }

    /**
     * 订单完成后的回应，返回值将直接输出给回调方
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @param  boolean $is_success 订单是否处理成功
     */
    protected function onComplete($data, $order, $order_extend, $is_success)
    {
        return $is_success?['errcode'=>200]:['errcode'=>0];
    }

    /**
     * 计算hmac-sha1签名
     * @param array $params
     * @param type $secretKey
     * @return type
     */
    private function sign(array $params, $secretKey){
        $sortString = $this->buildSortString($params);
        $signature = hash_hmac('sha1', $sortString, $secretKey,FALSE);

        return $signature;
    }

    /**
     * 构造排序字符串
     * @param array $params
     * @return string
     */
    private function buildSortString(array $params) {
        if(empty($params)){
            return '';
        }

        ksort($params);

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }

        return implode('&',$fields);
    }
}
