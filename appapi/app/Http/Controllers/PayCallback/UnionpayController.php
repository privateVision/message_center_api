<?php //银联技术文档地址：https://open.unionpay.com/ajweb/product/detail?id=3
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class UnionpayController extends Controller
{

    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['orderId'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend) {
        return $data['queryId'];
    }

    protected function verifySign($data, $order, $order_extend) {
        $config = configex('common.payconfig.unionpay');

        $sign = $data['signature'];
        unset($data['signature']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            if($v === '') continue;
            $str .= "{$k}={$v}&";
        }
        $str = trim($str, '&');

        $public_key = openssl_x509_read(file_get_contents($config['verify']));
        $sign = base64_decode($sign);
        $params_sha1x16 = sha1($str, FALSE);
        return openssl_verify($params_sha1x16, $sign, $public_key, OPENSSL_ALGO_SHA1);
    }

    protected function handler($data, $order, $order_extend){
        return true;
    }

    // 商户返回码为200时，银联判定为通知成功，其他返回码为通知失败。
    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null) {
        return http_response_code(200);
    }
}