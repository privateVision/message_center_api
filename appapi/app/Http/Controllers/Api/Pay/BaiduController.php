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
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        return [
            'data' => array()
        ];
    }


    /**
     * 获取百度平台用户id
     */
    public function getBaiduAccout() {
        $token = $this->parameter->tough('token');

        $appid = $this->procedure_extend->third_appid;
        $appkey = $this->procedure_extend->third_appkey;

        $params = array(
            'AppID'=>$appid,
            'AccessToken'=>$token,
            'Sign'=>self::verify([$appid, $token, $appkey])
        );

        $url = self::PayHttp . 'CpLoginStateQuery.ashx';
        $res = http_curl($url, $params, true);
        if($res['cd'] == 1 && $res['Sign']==self::verify([$appid, $res['ResultCode'], urldecode($res['Content']), $appkey])) {
            $result = base64_decode(urldecode($res['Content']));
            return json_decode($result,true);
        } else {
            throw new ApiException(ApiException::Remind, $res['rspmsg']);
        }
    }

    /**
     * 查询百度平台订单状态
     */
    public function getOrderInfo() {
        $order_id = $this->parameter->tough('order_id');
        $appid = $this->procedure_extend->third_appid;
        $appkey = $this->procedure_extend->third_appkey;

        $params = array(
            'AppID'=>$appid,
            'CooperatorOrderSerial'=>$order_id,
            'Sign'=>self::verify([$appid, $order_id, $appkey]),
            'OrderType'=>1,
            'Action'=>'10002'
        );

        $url = self::PayHttp . 'CpOrderQuery.ashx';
        $res = http_curl($url, $params, true);
        if($res['cd'] == 1 && $res['Sign']==self::verify([$appid, $res['ResultCode'], urldecode($res['Content']), $appkey])) {
            $result = base64_decode(urldecode($res['Content']));
            return json_decode($result,true);
        } else {
            throw new ApiException(ApiException::Remind, $res['rspmsg']);
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