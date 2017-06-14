<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Orders;
use App\Model\OrderExtend;

class UcController extends Controller {

    use RequestAction;

    const PayMethod = '-11';
    const PayText = 'uc';
    const PayTypeText = 'uc平台支付';

    /**
     * uc config
     * {
     *      "cp_id":"71659",
     *      "app_key":"c763721105d3b331fa160bf303baca2c"
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_key'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        //获取uc用户id
        $accountId = $this->parameter->tough('account_id');

        $params['accountId'] = $accountId;
        $params['amount'] = $real_fee/100;
        $params['notifyUrl'] = urlencode(url('pay_callback/uc'));
        $params['cpOrderId'] = $this->parameter->tough('order_id');
        $params['callbackInfo'] = '';

        $notInKey =  array("roleName", "roleId", "grade", "serverId", "signType");
        $params['sign'] = self::verify($cfg, $params, $notInKey);
        $params['signType'] = 'MD5';

        return [
            'data' => $params
        ];
    }

    protected static function verify($cfg, $params, $notInKey = array()) {
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }
        return md5($enData.$cfg['app_key']);
    }
}