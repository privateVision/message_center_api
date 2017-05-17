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
        
        $data['hash'] = mycard_hash($data, $config['FacServerKey']);

        //获取authtoken
        $res = http_curl($config['authcode_quey_url'].'MyBillingPay/api/AuthGlobal', $data, 'POST');

        return ['data' => $res];
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