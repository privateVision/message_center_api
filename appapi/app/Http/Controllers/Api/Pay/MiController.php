<?php

namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;

class MiController extends Controller
{
    use RequestAction;

    const PayMethod = '-17';
    const PayText = 'xiaomi';
    const PayTypeText = '小米平台支付';

    /**
     * xiaomi config
     * {
     *      "app_id":"2882303761517413186",
     *      "app_key":"5861741367186",
     *      "app_secret":"CP8gFYiTUX25qat8xRKwHQ=="
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param OrderExtend $order_extend
     * @param $real_fee
     * @return array
     * @internal param $accountId
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        return [
            'data' => array()
        ];
    }

    /**
     * 小米订单查询接口
     * @param order_id
     * @param uid    小米平台用户uid
     */
    public function queryOrderAction() {
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('message.error_third_params'));
        }

        $params = array(
            'appId'=>$cfg['app_id'],
            'cpOrderId'=>$this->parameter->tough('order_id'),
            'uid'=>$this->parameter->tough('uid')
        );
        $params['signature'] =  self::sign($params, $cfg['app_secret']);
        $url = 'http://mis.migc.xiaomi.com/api/biz/service/queryOrder.do';
        $res = http_curl($url, $params, false);
        return $res;
    }

    /**
     * 计算hmac-sha1签名
     * @param array $params
     * @param type $secretKey
     * @return type
     */
    private function sign(array $params, $secretKey){
        $sortString = $this->buildSortString($params);
        $signature = hash_hmac('sha1', $sortString, $secretKey,FALSE);

        return $signature;
    }

    /**
     * 构造排序字符串
     * @param array $params
     * @return string
     */
    private function buildSortString(array $params) {
        if(empty($params)){
            return '';
        }

        ksort($params);

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }

        return implode('&',$fields);
    }
}
