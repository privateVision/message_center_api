<?php

namespace App\Http\Controllers\Api\Pay;

use App\Model\OrderExtend;
use App\Model\Orders;
use Illuminate\Http\Request;

class VivoController extends Controller
{
    use RequestAction;

    const PayMethod = '-13';
    const PayText = 'vivo';
    const PayTypeText = 'vivo平台支付';

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee)
    {
        $params = [
            'version' => '1.0.0',
            'cpId' => $this->procedure_extend->third_cpid,
            'appId' => $this->procedure_extend->third_appid,
            'cpOrderNumber' => $order->id,
            'notifyUrl' => url()->previous().'pay_callback/vivo',
            'orderTime' => date('YmdHis', strtotime($order->createTime)),
            'orderAmount' => $real_fee,
            'orderTitle' => $order->subject,
            'orderDesc' => $order->body,
            'extInfo' => 1
        ];

        $sign = self::sign($params);

        $params['signMethod'] = 'MD5';
        $params['signature'] = $sign;

        $url = 'https://pay.vivo.com.cn/vcoin/trade';

        $res = http_curl($url, $params);

        if($res['cd'] == 1 && isset($res['respCode'])){
            if($res['respCode'] == 200){
                return $res;
            } else {
                throw new ApiException(ApiException::Remind, $res['respCode'].':'.$res['respMsg']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }

    }

    protected function sign($params=[])
    {
        ksort($params);

        $sign = md5(http_build_query($params).'&'.md5($this->procedure_extend->third_appkey));

        return $sign;
    }

}
