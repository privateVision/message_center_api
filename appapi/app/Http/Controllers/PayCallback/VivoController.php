<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class VivoController extends Controller
{
    /**
     * vivo config
     * {
     *      "cp_id":"20160504231334318356",
     *      "app_id":"f28613464145a3a861869bc4ae35335f",
     *      "app_key":"81tKZhcpxI0wOoGgSwcgwk0WC"
     * }
     */

    /**
     * 获取回调的所有数据
     * @param  Request $request
     * @return mixed
     */
    protected function getData(Request $request)
    {
        return $_POST;
    }

    /**
     * 获取我方订单号
     * @param mixed $data getData方法返回的数据
     */
    protected function getOrderNo($data)
    {
        return $data['cpOrderNumber'];
    }

    /**
     * 获取第三方订单号（微信，支付宝等）
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['orderNumber'];
    }

    /**
     * 验证签名
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @return bool
     */
    protected function verifySign($data, $order, $order_extend)
    {
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();

        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_id'])) {
            return false;
        }

        $params = [
            'respCode' => $data['respCode'],
            'respMsg' => $data['respMsg'],
            'tradeType' => $data['tradeType'],
            'tradeStatus' => $data['tradeStatus'],
            'cpId' => $data['cpId'],
            'appId' => $data['appId'],
            'uid' => $data['uid'],
            'cpOrderNumber' => $data['cpOrderNumber'],
            'orderNumber' => $data['orderNumber'],
            'orderAmount' => $data['orderAmount'],
            'extInfo' => $data['extInfo'],
            'payTime' => $data['payTime']
        ];

        if($data['signMethod']!='MD5')return false;

        $sign = self::sign($params, $cfg['app_key']);

        return $data['signature'] == $sign;
    }

    /**
     * 订单特殊处理逻辑判断，如果返回false则视为订单支付失败
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function handler($data, $order, $order_extend)
    {
        return $data['respCode'] == 200;
    }

    /**
     * 订单完成后的回应，返回值将直接输出给回调方
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @param  boolean $is_success 订单是否处理成功
     */
    protected function onComplete($data, $order, $order_extend, $is_success, $message = null)
    {
        return $is_success ? 'success':'fail';
    }

    private function sign($params, $appkey)
    {
        ksort($params);

        $str = '';
        foreach($params as $k=>$v) {
            if(!empty($v)) {
                $str .=  $k.'='.$v.'&';
            }
        }

        return md5($str . strtolower(md5($appkey)));
    }
}
