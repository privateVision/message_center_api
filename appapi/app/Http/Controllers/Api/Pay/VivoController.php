<?php

namespace App\Http\Controllers\Api\Pay;

use App\Model\OrderExtend;
use App\Model\Orders;
use App\Exceptions\ApiException;

class VivoController extends Controller
{
    use RequestAction;

    const PayMethod = '-13';
    const PayText = 'vivo';
    const PayTypeText = 'vivo平台支付';

    /**
     * vivo config
     * {
     *      "cp_id":"20160504231334318356",
     *      "app_id":"f28613464145a3a861869bc4ae35335f",
     *      "app_key":"18d8c752c7f91f1966e498bba54bf490"
     * }
     */

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee)
    {
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $params = [
            'version' => '1.0.0',
            'cpId' => $cfg['cp_id'],
            'appId' => $cfg['app_id'],
            'cpOrderNumber' => $order->sn,
            'notifyUrl' => url('pay_callback/vivo'),
            'orderTime' => date('YmdHis', strtotime($order->createTime)),
            'orderAmount' => $real_fee,
            'orderTitle' => mb_substr($order->subject, 0, 45),
            'orderDesc' => mb_substr($order->body, 0, 100),
            'extInfo' => '111'
        ];

        $sign = self::sign($params, $cfg['app_key']);

        $params['signMethod'] = 'MD5';
        $params['signature'] = $sign;

        $url = 'https://pay.vivo.com.cn/vcoin/trade';

        $res = http_curl($url, $params);

        if(isset($res['respCode'])){
            if($res['respCode'] == 200){
                return $res;
            } else {
                throw new ApiException(ApiException::Remind, $res['respCode'].':'.$res['respMsg']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }

    }

    protected function sign($params, $appkey)
    {
        ksort($params);

        $str = '';
        foreach($params as $k=>$v) {
            if(!empty($v)) {
                $str .=  $k.'='.$v.'&';
            }
        }

        return md5($str . strtolower(md5($appkey)));
    }

}
