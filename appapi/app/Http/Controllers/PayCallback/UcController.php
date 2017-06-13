<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class UcController extends Controller
{
    /**
     * uc config
     * {
     *      "cp_id":"71659",
     *      "app_key":"c763721105d3b331fa160bf303baca2c"
     * }
     */

    protected function getData(Request $request)
    {
        $request = file_get_contents("php://input");
        return json_decode($request, true);
    }

    protected function getOrderNo($data)
    {
        return $data['data']['cpOrderId'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend)
    {
        return $data['data']['orderId'];
    }

    protected function verifySign($data, $order, $notInKey = array())
    {
        $proceduresExtend = ProceduresExtend::where('pid', $order->vid)->first();
        $cfg = json_decode($proceduresExtend->third_config, true);
        if(empty($cfg) || !isset($cfg['app_id'])) {
            return false;
        }

        $params = $data['data'];
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }

        $sign = md5($enData.$cfg['app_key']);
        if($data['sign'] == $sign){
            return true;
        }
        return false;
    }

    protected function handler($data, $order, $order_extend)
    {
        return $data['data']['orderStatus'] == 'S'?true:false;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null)
    {
        return $isSuccess?'SUCCESS':'FAILURE';
    }
}
