<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;

class MycardController extends Controller {

    use RequestAction;

    const PayType = '-7';
    const PayTypeText = 'MyCard';
    const EnableStoreCard = true;
    const EnableCoupon = true;
    const EnableBalance = true;

    public function payHandle(Orders $order, $real_fee) {
        $config = config('common.payconfig.mycard');
        
        $data['FacServiceId'] = $config['FacServiceId'];
        $data['FacTradeSeq'] = $order->sn;
        $data['TradeType'] = '2';
        $data['ServerId'] = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $data['CustomerId'] = $order->ucid;
        $data['ProductName'] = $order->subject;
        $data['Amount'] = $real_fee;
        $data['Currency'] = 'TWD';
        $data['SandBoxMode'] = env('APP_DEBUG') ? 'true' : 'false';
        
        $data['hash'] = static::mycard_hash($data, $config['FacServerKey']);

        //获取authtoken
        $result = http_request($config['autocode_url'], $data, true);

        log_debug('mycard-authcode-request', ['resdata' => $result, 'reqdata' => $data], $config['autocode_url']);

        // json decode
        $result = json_decode($result, true);
        if(!$result) {
            throw new ApiException(ApiException::Remind, 'MyCard 支付请求失败');
        }

        if(@$result['ReturnCode'] != '1' || empty(@$result['AuthCode'])) {
            throw new ApiException(ApiException::Remind, $result['ReturnMsg']);
        }

        return [
            'type' => 'webview',
            'url' => $config['webpay_url'] .'?AuthCode='. $result['AuthCode'],
        ];
    }

    protected static function mycard_hash($data, $key) {
        $prms = array_values($data);
        $prms[] = $key;
        $preStr = implode('', $prms);
        $hash = urlencode($preStr);

        $sign = hash('sha256', $hash, true);

        return bin2hex($sign);
    }
}