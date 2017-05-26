<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class WechatController extends Controller {

    use RequestAction;

    const PayType = '-5';
    const PayTypeText = '微信';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Orders $order, $real_fee) {
        $restype = $this->parameter->get('restype');

        $config = config('common.payconfig.wechat');

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
        
        // curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mch.weixin.qq.com/pay/unifiedorder');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, $config['pemfile_cert']);
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, $config['pemfile_key']);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dom->saveXML());

        $result = curl_exec($ch);
        curl_close($ch);
        
        log_debug('微信统一下单', ['url' => 'https://api.mch.weixin.qq.com/pay/unifiedorder', 'reqdata' => $data, 'resdata' => $result]);

        if(!$result) {
            throw new ApiException(ApiException::Remind, '发起支付失败');
        }
        
        $responseData = [];

        $xml = simplexml_load_string($result);
        foreach($xml as $key => $value){
            $responseData[$key]=(string)$value;
        }

        $responseData['out_trade_no'] = $order->sn;
        
        if(!@$responseData['return_code']) {
            throw new ApiException(ApiException::Remind, '发起支付失败');
        }
        
        if($responseData['return_code'] != 'SUCCESS') {
            throw new ApiException(ApiException::Remind, '发起支付失败（'.$responseData['return_msg'].'）');
        }
        
        $responseData['timeStamp'] = time();
        $responseData['package'] = "Sign=WXPay";
        $responseData['sign'] = strtoupper(md5("appid={$config['appid']}&noncestr={$responseData['nonce_str']}&package={$responseData['package']}&partnerid={$config['mch_id']}&prepayid={$responseData['prepay_id']}&timestamp={$responseData['timeStamp']}&key={$config['key']}"));

        if($restype  == 'protocol') {
            return ['protocol' => sprintf('weixin://app/%s/pay/?%s', $config['appid'], http_build_query([
                'nonceStr' => $responseData['nonce_str'],
                'package' => $responseData['package'],
                'partnerId' => $config['mch_id'],
                'prepayId' => $responseData['prepay_id'],
                'timeStamp' => $responseData['timeStamp'],
                'sign' => $responseData['sign'],
            ])), 'restype' => $restype];
        } else {
            return $responseData;
        }
    }
}