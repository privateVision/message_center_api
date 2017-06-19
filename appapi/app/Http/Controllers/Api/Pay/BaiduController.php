<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class BaiduController extends Controller {

    use RequestAction;

    const PayMethod = '-10';
    const PayText = 'baidu';
    const PayTypeText = '百度平台支付';
    const PayHttp = 'http://querysdkapi.91.com/';

    /**
     * baidu config
     * {
     *      "app_id":"8118120",
     *      "app_key":"zwjMlKrGf7mWwDHU7x7GvE9z",
     *      "app_secret":"Nfawzw7X7grqiuLkyOCGrxW2YEa3AuQ0"
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {

        $uid = $this->parameter->tough('uid');

        return [
            'data' => array(
                'cooperatorOrderSerial'=>$order->sn,
                'productName'=>$order->subject,
                'totalPriceCent'=>$real_fee,
                'ratio'=>0,
                'extInfo'=>'111',
                'uid'=>$uid
            )
        ];
    }

    /**
     * 查询百度平台订单状态
     */
    public function getOrderInfoAction() {
        $order_id = $this->parameter->tough('order_id');
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_key'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        $params = array(
            'AppID'=>$cfg['app_id'],
            'CooperatorOrderSerial'=>$order_id,
            'Sign'=>self::verify([$cfg['app_id'], $order_id, $cfg['app_key']]),
            'OrderType'=>1,
            'Action'=>'10002'
        );

        $url = self::PayHttp . 'CpOrderQuery.ashx';
        $res = http_curl($url, $params);
        if(isset($res['Sign']) && $res['Sign']==self::verify([$cfg['app_id'], $res['ResultCode'], urldecode($res['Content']), $cfg['app_key']])) {
            $result = base64_decode(urldecode($res['Content']));
            return json_decode($result, true);
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }

    /**
     * 计算签名
     * @param $params
     * @return string
     */
    protected function verify($params) {
        $v = array_values($params);
        return md5($v);
    }
}