<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;

class WechatController extends Controller {

    use RequestAction;

    const PayMethod = '-5';
    const PayText = 'wechat';
    const PayTypeText = '微信';

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee)
    {
        // XXX 兼容旧版IOS返回scheme

        $restype = $this->parameter->get('restype');
        if($restype  == 'protocol') {
            return [
                'restype' => 'web_url', // TODO 不知道加这个参数搞什么鬼
                'protocol' => $this->getUrlScheme($config, $order, $order_extend, $real_fee),
            ];
        } else {
            return $this->request($config, $order, $real_fee);
        }
    }

    public function getUrlScheme($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $responseData = $this->request($config, $order, $real_fee);

        return sprintf('weixin://app/%s/pay/?%s', $config['appid'], http_build_query([
            'nonceStr' => $responseData['nonce_str'],
            'package' => $responseData['package'],
            'partnerId' => $config['mch_id'],
            'prepayId' => $responseData['prepay_id'],
            'timeStamp' => $responseData['timeStamp'],
            'sign' => $responseData['sign'],
        ]));
    }

    public function request($config, Orders $order, $real_fee) {
        // 请求参数
        $data['appid'] = $config['appid'];
        $data['mch_id'] = $config['mch_id'];
        $data['nonce_str'] = md5(uuid());
        $data['body'] = $order->subject;
        $data['out_trade_no'] = $order->sn;
        $data['total_fee'] = env('APP_DEBUG', true) ? 1 : $real_fee;
        $data['spbill_create_ip'] = getClientIp();
        $data['notify_url'] = url('pay_callback/wechat');
        $data['trade_type'] = 'APP';

        // 加密算法
        ksort($data);

        $str = '';
        foreach($data as $k => $v){
            $str .= $k .'='. $v .'&';
        }

        $str .= 'key='. $config['key'];
        $data['sign'] = strtoupper(md5($str));

        // 将数据转换成xml
        $dom = new \DOMDocument("1.0");
        $rootNode = $dom->createElement("root");
        $dom->appendChild($rootNode);
        foreach($data as $k => $v) {
            $node = $dom->createElement($k);
            $value = $dom->createTextNode($v);
            $node->appendChild($value);

            $rootNode->appendChild($node);
        }

//        // curl
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, 'https://api.mch.weixin.qq.com/pay/unifiedorder');
//        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
//        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
//        curl_setopt($ch,CURLOPT_SSLCERT, $config['pemfile_cert']);
//        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
//        curl_setopt($ch,CURLOPT_SSLKEY, $config['pemfile_key']);
//        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $dom->saveXML());
//
//        $result = curl_exec($ch);
//        curl_close($ch);
//
//        log_debug('微信统一下单', ['url' => 'https://api.mch.weixin.qq.com/pay/unifiedorder', 'reqdata' => $data, 'resdata' => $result]);

        $result = http_curl('https://api.mch.weixin.qq.com/pay/unifiedorder', $dom->saveXML(), true, array(
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$config['pemfile_cert'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$config['pemfile_key'],
            CURLOPT_NOSIGNAL=>1,
            CURLOPT_HTTPHEADER=>array('Expect:')
        ), 'str');

        if(!$result) {
            throw new ApiException(ApiException::Remind, trans('messages.pay_fail'));
        }

        $responseData = [];

        $xml = simplexml_load_string($result);
        foreach($xml as $key => $value){
            $responseData[$key]=(string)$value;
        }

        $responseData['out_trade_no'] = $order->sn;

        if(!@$responseData['return_code']) {
            throw new ApiException(ApiException::Remind, trans('messages.pay_fail'));
        }

        if($responseData['return_code'] != 'SUCCESS') {
            throw new ApiException(ApiException::Remind, trans('messages.pay_fail_1', ['return_msg）' => $responseData['return_msg']]));
        }

        $responseData['timeStamp'] = time();
        $responseData['package'] = "Sign=WXPay";
        $responseData['sign'] = strtoupper(md5("appid={$config['appid']}&noncestr={$responseData['nonce_str']}&package={$responseData['package']}&partnerid={$config['mch_id']}&prepayid={$responseData['prepay_id']}&timestamp={$responseData['timeStamp']}&key={$config['key']}"));

        return $responseData;
    }
}