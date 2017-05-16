<?php // 银联技术文档地址：https://open.unionpay.com/ajweb/product/detail?id=3
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class UnionpayController extends Controller {

    use RequestAction;

    const PayType = '-2';
    const PayTypeText = '银联';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Orders $order, $real_fee) {
        $config = config('common.payconfig.unionpay');

        openssl_pkcs12_read(base64_decode($config['pfx']), $cert, $config['pfx_pwd']);
        $x509 = openssl_x509_read($cert['cert']);
        $certinfo = openssl_x509_parse($x509);
        openssl_x509_free($x509);
        $certid = $certinfo['serialNumber'];

        $data['version'] = '5.0.0';
        $data['encoding'] = 'utf-8';
        $data['backUrl'] = url('pay_callback/unionpay');
        $data['accessType'] = '0';
        $data['merId'] = $config['merid'];
        $data['currencyCode'] = '156';
        $data['signMethod'] = '01';
        $data['certId'] = $certid;
        $data['orderId'] = $order->sn;
        $data['txnTime'] = date('YmdHis');
        $data['txnAmt']  = env('APP_DEBUG', true) ? 1 : $real_fee;
        $data['txnType'] = '01';
        $data['bizType'] = '000201';
        $data['txnSubType'] = '01';
        $data['channelType'] ='08';
        ksort($data);
        $data['signature'] = static::rsaSign(sha1(static::encode($data)), $cert['pkey']);

        $res = http_request($config['trade_url'], $data, true);

        log_info('unionpayRequest', ['reqdata' => $data, 'resdata' => $res]);

        if(!$res) {
            throw new ApiException(ApiException::Remind, '银联支付请求失败'); //LANG:union_request_fail
        }

        parse_str($res, $resdata);

        // todo: 是否该把银联返回的错误消息返回给用户
        if($resdata['respCode'] !== '00') {
            throw new ApiException(ApiException::Remind, '银联支付请求失败 ' . $resdata['respMsg']); //LANG:union_request_fail
        }

        return ['tn' => $resdata['tn']];
    }

    protected static function encode($data) {
        ksort($data);
        $str = [];
        foreach($data as $k => $v) {
            if($v == "") continue;
            $str[] = "{$k}={$v}";
        }

        return implode('&', $str);
    }

    protected static function rsaSign($str, $prikey) {
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key);
        openssl_free_key($key);
        return base64_encode($sign);
    }
}