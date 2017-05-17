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

        // data的key顺序不能变
        $data['FacServiceId'] = $config['FacServiceId'];
        $data['FacTradeSeq'] = $order->sn;
        $data['TradeType'] = '2';
        $data['ServerId'] = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $data['CustomerId'] = $order->ucid;
        $data['ProductName'] = $order->subject ?: "props";
        $data['Amount'] = number_format($real_fee / 100, 2);
        $data['Currency'] = 'TWD';
        $data['SandBoxMode'] = env('APP_DEBUG') ? 'true' : 'false';

        $data['hash'] = static::mycard_hash($data, $config['FacServerKey']);

        //获取authtoken
        $result = http_request($config['autocode_url'], $data, true);

        log_debug('mycard-authcode-request', ['resdata' => $result, 'reqdata' => $data], $config['autocode_url']);

        // json decode
        $result = json_decode($result, true);
        if(!$result) {
            throw new ApiException(ApiException::Remind, trans('messages.mycard_request_fail'));
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
        $_data = array_values($data);
        $_data[] = $key;

        $str = implode('', $_data);
        $str = urlencode($str);

        $str = preg_replace_callback('/%[0-9A-F]{2}/', function($matches) {
            return strtolower($matches[0]);
        }, $str);

        $sign = hash('sha256', $str, true);

        return bin2hex($sign);
    }
}