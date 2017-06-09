<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class BaiduController extends Controller
{
    //
    protected function getData(Request $request)
    {
        return $_POST;
    }

    protected function getOrderNo($data)
    {
        return $data['CooperatorOrderSerial'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['OrderSerial'];
    }

    protected function verifySign($data, $order, $order_extend)
    {
        $result = ProceduresExtend::where('pid', $order->vid)->first()->toArray;
        if($data['Sign'] == self::verify([$result['third_appid'], $data['OrderSerial'], $data['CooperatorOrderSerial'], urldecode($data['Content']), $result['third_appkey']])) {
            return true;
        }

        return false;
    }

    protected function handler($data, $order, $order_extend)
    {
        $content = base64_decode(urldecode($data['Content']));
        $res = json_decode($content, true);
        if($res['OrderStatus'] == 1) {
            return true;
        }

        return false;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null)
    {
        $result = ProceduresExtend::where('pid', $order->vid)->first()->toArray;

        $code = $isSuccess?1:0;
        $sign =  self::verify([$result['third_appid'], $code, $result['third_appkey']]);

        return json_encode([
            'AppID'=>$data['AppID'],
            'ResultCode'=>$code,
            'ResultMsg'=>'success',
            'Sign'=>$sign
        ]);
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
