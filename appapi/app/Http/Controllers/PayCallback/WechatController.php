<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class WechatController extends Controller {
    
    protected function getData(Request $request) {
        $content = file_get_contents('php://input', 'r');
        $xml = simplexml_load_string($content);
        foreach($xml as $k => $v) {
            $data[strval($k)] = strval($v);
        }
        
        return $data;
    }

    protected function getOrderNo($data) {
        return $data['out_trade_no'];
    }

    protected function getTradeOrderNo($data, $order) {
        return $data['transaction_id'];
    }
    
    protected function verifySign($data, $order) {
        $config = config('common.payconfig.wechat');
        
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        
        $str = '';
        foreach($data as $k => $v){
            $str .= $k .'='. $v .'&';
        }
        $str .= 'key='. $config['key'];
        
        $_sign = strtoupper(md5($str));
        
        return $sign === $_sign;
    }
    
    protected function handler($data, $order){
        return true;
    }
    
    protected function handler($data, $order) {
        return $data['result_code'] == 'SUCCESS';
    }
    
    protected function onComplete($data, $order, $isSuccess) {
        if($isSuccess) {
            return "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
        } else {
            return "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[ERROR]]></return_msg></xml>";
        }
    }
}
