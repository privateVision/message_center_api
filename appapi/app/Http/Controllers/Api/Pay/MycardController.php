<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;

class MycardController extends Controller {

    use RequestAction;

    const PayMethod = '-7';
    const PayText = 'mycard';
    const PayTypeText = 'MyCard';

    public function getUrl($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $data['FacServiceId'] = $config['FacServiceId'];
        $data['FacTradeSeq'] = $order->sn;
        $data['TradeType'] = '2';
        $data['ServerId'] = getClientIp();
        $data['CustomerId'] = $order->ucid;
        $data['ProductName'] = $order->subject ?: "props";
        $data['Amount'] = number_format($real_fee / 100, 2, '.', '');
        $data['Currency'] = 'TWD';
        $data['PaymentType'] = "";
        $data['ItemCode'] = "";
        $data['SandBoxMode'] = env('APP_DEBUG') ? 'true' : 'false';

        $data['hash'] = static::mycard_hash($data, $config['FacServerKey']);

        //获取authtoken
        $result = http_request($config['AuthGlobal'], $data, true);

        log_debug('mycard-authcode-request', ['resdata' => $result, 'reqdata' => $data], $config['AuthGlobal']);

        // json decode
        $result = json_decode($result, true);
        if(!$result) {
            throw new ApiException(ApiException::Remind, trans('messages.mycard_request_fail'));
        }

        if(@$result['ReturnCode'] != '1' || empty(@$result['AuthCode'])) {
            throw new ApiException(ApiException::Remind, $result['ReturnMsg']);
        }

        // 记录AuthCode
        $order_extend->extra_params = ['AuthCode' => $result['AuthCode'], 'TradeSeq' => @$result['TradeSeq']];

        return $config['MyCardPay'] .'?AuthCode='. $result['AuthCode'];
    }

    protected static function mycard_hash($data, $key) {
        $str = implode('', [
            $data['FacServiceId'],
            $data['FacTradeSeq'],
            $data['TradeType'],
            $data['ServerId'],
            $data['CustomerId'],
            $data['ProductName'],
            $data['Amount'],
            $data['Currency'],
            $data['SandBoxMode'],
            $key
        ]);

        $str = urlencode($str);

        $str = preg_replace_callback('/%[0-9A-F]{2}/', function($matches) {
            return strtolower($matches[0]);
        }, $str);

        $sign = hash('sha256', $str, true);

        return bin2hex($sign);
    }
}