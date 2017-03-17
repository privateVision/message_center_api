<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrdersExt;

class NowpayController extends Controller {

    public function WechatAction(Request $request, Parameter $parameter) {
        $balance = $parameter->get('balance');
        $order_id = $parameter->tough('order_id');

        $order = $this->payOrder($order_id, Orders::Way_Wechat, $balance);
        $config = config('common.nowpay.wechat');

        $mht['appId'] = $config['appId'];
        //$mht['consumerId'] = $this->user->ucid;
        //$mht['consumerName'] = $this->user->uid;
        $mht['mhtCharset'] = $config['mhtCharset'];
        $mht['mhtCurrencyType'] = $config['mhtCurrencyType'];
        $mht['mhtOrderAmt'] = (env('APP_DEBUG', true) ? 0.01 : $order->real_fee()) * 100;
        $mht['mhtOrderName'] = $order->subject;
        $mht['mhtOrderDetail'] = $order->body;
        $mht['mhtOrderNo'] = $order->sn;
        $mht['mhtOrderStartTime'] = date($config['dtFormat']);
        //$mht['mhtOrderTimeOut'] = $config['mhtOrderTimeOut']
        $mht['mhtOrderType'] = $config['mhtOrderType'];
        $mht['notifyUrl'] = url('pay_callback/nowpay_wechat');
        $mht['payChannelType'] = $config['payChannelType'];
        ksort($mht);
        $mht['mhtSignature'] = md5(static::encode($mht) .'&'. md5($config['secure_key']));
        $mht['mhtSignType'] = $config['mhtSignType'];

        return ['data' => static::encode($mht)];
    }

    public function AlipayAction(Request $request, Parameter $parameter) {
        $balance = $parameter->get('balance');
        $order_id = $parameter->tough('order_id');

        $order = $this->payOrder($order_id, Orders::Way_Alipay, $balance);
        $config = config('common.nowpay.alipay');

        $data = sprintf('partner="%s"', $config['AppID']);
        $data.= sprintf('&out_trade_no="%s"', $order->sn);
        $data.= sprintf('&subject="%s"', $order->subject);
        $data.= sprintf('&body="%s"', $order->body);
        $data.= sprintf('&total_fee="%.2f"', env('APP_DEBUG', true) ? 0.01 : $order->real_fee());
        $data.= sprintf('&notify_url="%s"', urlencode(url('pay_callback/nowpay_alipay')));
        $data.= '&service="mobile.securitypay.pay"';
        $data.= '&_input_charset="UTF-8"';
        $data.= '&payment_type="1"';
        $data.= sprintf('&seller_id="%s"', $config['AppID']);
        $data.= sprintf('&sign="%s"', urlencode(static::rsaSign($data, file_get_contents($config['PriKey']))));
        $data.= '&sign_type="RSA"';

        return ['data' => $data];
    }

    public function UnionpayAction(Request $request, Parameter $parameter) {
        $balance = $parameter->get('balance');
        $order_id = $parameter->tough('order_id');

        $order = $this->payOrder($order_id, Orders::Way_UnionPay, $balance);
        $config = config('common.nowpay.unionpay');

        openssl_pkcs12_read(base64_decode($config['pfx']), $cert, $config['pfx_pwd']);
        $x509 = openssl_x509_read($cert['cert']);
        $certinfo = openssl_x509_parse($x509);
        openssl_x509_free($x509);
        $certid = $certinfo['serialNumber'];

        $data['version'] = '5.0.0';
        $data['encoding'] = 'utf-8';
        $data['backUrl'] = url('pay_callback/nowpay_unionpay');
        $data['accessType'] = '0';
        $data['merId'] = $config['merid'];
        $data['currencyCode'] = '156';
        $data['signMethod'] = '01';
        $data['certId'] = $certid;
        $data['orderId'] = $order->sn;
        $data['txnTime'] = date('YmdHis');
        $data['txnAmt']  = (env('APP_DEBUG', true) ? 0.01 : $order->real_fee()) * 100;
        $data['txnType'] = '01';
        $data['bizType'] = '000201';
        $data['txnSubType'] = '01';
        $data['channelType'] ='08';
        ksort($data);
        $data['signature'] = static::rsaSign(sha1(static::encode($data)), $cert['pkey']);

        $res = http_request($config['trade_url'], $data, true);

        log_info('unionpayRequest', ['reqdata' => $data, 'resdata' => $res]);

        if(!$res) {
            throw new ApiException(ApiException::Remind, '银联支付请求失败');
        }

        parse_str($res, $resdata);

        // todo: 是否该把银联返回的错误消息返回给用户
        if($resdata['respCode'] !== '00') {
            throw new ApiException(ApiException::Remind, '银联支付请求失败 ' . $resdata['respMsg']);
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