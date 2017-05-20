<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;

class UcController extends Controller {

    use RequestAction;

    const PayMethod = '-11';
    const PayText = 'uc';
    const PayTypeText = 'uc平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, $real_fee, Request $request) {
        $params['accountId'] = $this->parameter->tough('accountId');
        $params['amount'] = $real_fee;
        $params['notifyUrl'] = urlencode(url('pay_callback/uc'));
        $params['cpOrderId'] = $this->parameter->tough('order_id');
        $params['callbackInfo'] = '';

        $notInKey =  array("roleName", "roleId", "grade", "serverId", "signType");
        $params['sign'] = self::verify($config, $params, $notInKey);

        return [
            'data' => $params
        ];
    }

    protected static function verify($config, $params, $notInKey = array()) {
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }
        return md5($enData.$config['apikey']);
    }


}