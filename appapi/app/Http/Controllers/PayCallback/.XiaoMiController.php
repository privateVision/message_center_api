<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/6
 * Time: 11:47
 */

namespace App\Controller\External;

use App\Exceptions\PayCallbackException;
use Illuminate\Http\Request;
use App\Model\Orders;

class XiaoMiController extends \App\Controller
{
    protected function getData(Request $request) {
        return $_REQUEST;
    }

    protected function getOrderNo($data) {
        return $data['cpOrderId'];
    }

    protected function getTradeOrderNo($data, Orders $order) {
        return $data['orderId'];
    }

    protected function verifySign($data, Orders $order) {
        //支付签名
        $secret_key = "";
        $sign = $data['signature'];
        unset($data['signature']);

        $_sign = $this->sign($data,$secret_key);
        return $sign === $_sign;
        //验证当前的订单
    }

    protected function handler($data, Orders  $order){
        //验证当前的订单的信息 如果当前的订单验证通过，执行异步发货 查询当前的订单，成功添加到发货的队列中！
        order_success($order->Cporderid); //验证订单完成，发货！
        return true;
    }

    protected function onComplete($data, Orders $order, $isSuccess) {
        return $isSuccess ? 'success' : 'fail';
    }

    /**
     * 验证签名
     * @param array $params
     * @param type $signature
     * @param type $secretKey
     * @return type
     */
    public function verifySignature(array $params, $signature, $secretKey) {
        if(empty($params)){
            return '';
        }

        ksort($params);

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }

        $sortString =  implode('&',$fields);
        $tempSignature = hash_hmac('sha1', $sortString, $secretKey,FALSE);
        return $signature == $tempSignature ? TRUE : FALSE;
    }

    /*
     * 生成签名的方法操作
     *
     * @param array $params
     * @param type $secretKey
     * */

    protected function sign(array $params, $secretKey){
        $sortString = "";

        if(!empty($params)) {

            ksort($params);

            $fields = array();

            foreach ($params as $key => $value) {
                $fields[] = $key . '=' . $value;
            }

            $sortString =  implode('&', $fields);
        }

        $signature = hash_hmac('sha1', $sortString, $secretKey,FALSE);

        return $signature; //返回签名
    }


    /**
     * url encode 函数
     * @param type $item  数组或者字符串类型
     * @return type
     */
    public function urlEncode($item) {
        if(is_array($item)){
            return array_map(array(&$this,'urlEncode'), $item);
        }
        return rawurlencode($item);
    }

    /**
     * url decode 函数
     * @param type $item 数组或者字符串类型
     * @return type
     */
    public function urlDecode($item){
        if(is_array($item)){
            return array_map(array(&$this,'urlDecode'), $item);
        }
        return rawurldecode($item);
    }


    public function test(){
        echo "This is channel! ";
        http_request("",array()); //调用当前的验证
    }

    /**
     * 查询订单接口
     * @param type $userId
     * @param type $cpOrderId
     */
    function queryOrder($data,$sec_key) {

        $params = array('appId' => $data["appId"],'uid' => $data["uid"], 'cpOrderId' => $data['cpOrderId']);
        $sec_key = "";
        $signature = $this->sign($params, $sec_key);

        $params['signature'] = $signature;
        $response = http_request($data['qu_url'],$params); // 查询当前的订单信息，如果成功则发货
        echo $response;
    }

}