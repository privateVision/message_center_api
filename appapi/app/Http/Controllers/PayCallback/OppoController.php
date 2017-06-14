<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class OppoController extends Controller
{
    /**
     * oppo config
     * {
     *      "app_id":"2183622",
     *      "app_key":"81tKZhcpxI0wOoGgSwcgwk0WC",
     *      "app_secret":"2a838e1Aaef9412e5412d511a644a5b3",
     *      "pay_pub_key":"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmreYIkPwVovKR8rLHWlFVw7YDfm9uQOJKL89Smt6ypXGVdrAKKl0wNYc3/jecAoPi2ylChfa2iRu5gunJyNmpWZzlCNRIau55fxGW0XEu553IiprOZcaw5OuYGlf60ga8QT6qToP0/dpiL/ZbmNUO9kUhosIjEu22uFgR+5cYyQIDAQAB"
     * }
     */

    /**
     * 获取回调的所有数据
     * @param  Request $request
     */
    protected function getData ( Request $request )
    {
        return $_POST;
    }

    /**
     * 获取我方订单号
     * @param mixed $data getData方法返回的数据
     */
    protected function getOrderNo ( $data )
    {
        return $data['partnerOrder'];
    }

    /**
     * 获取第三方订单号（微信，支付宝等）
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    protected function getTradeOrderNo ( $data , $order, $order_extend )
    {
        return $data['notifyId'];
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

        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_id'])) {
            return false;
        }

        return $this->rsa_verify($data, $cfg['pay_pub_key'])==1?true:false;
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
        return $is_success?'result=OK&resultMsg=ok':'result=FAIL&resultMsg=fail';
    }

    private function rsa_verify($contents, $publickey) {
        $str_contents = "notifyId={$contents['notifyId']}&partnerOrder={$contents['partnerOrder']}&productName={$contents['productName']}&productDesc={$contents['productDesc']}&price={$contents['price']}&count={$contents['count']}&attach={$contents['attach']}";

        $pem = chunk_split($publickey,64,"\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        $public_key_id = openssl_pkey_get_public($pem);
        $signature =base64_decode($contents['sign']);
        return openssl_verify($str_contents, $signature, $public_key_id );//成功返回1,0失败，-1错误,其他看手册
    }
}
