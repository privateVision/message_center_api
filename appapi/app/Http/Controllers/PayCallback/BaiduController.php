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
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();
        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_key'])) {
            return false;
        }

        if($data['Sign'] == self::verify([$cfg['third_appid'], $data['OrderSerial'], $data['CooperatorOrderSerial'], urldecode($data['Content']), $cfg['third_appkey']])) {
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
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();
        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_key'])) {
            return false;
        }

        $code = $isSuccess?1:0;
        $sign =  self::verify([$cfg['app_id'], $code, $cfg['app_key']]);

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
