<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class UcController extends Controller
{
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
        $params = $data['data'];

        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }

        $sign = md5($enData.config('common.payconfig.uc.apikey'));
        if($data['sign'] == $sign){
            return true;
        }
        return false;
    }

    protected function handler($data, $order, $order_extend)
    {
        return $data['data']['orderStatus'] == 'S'?true:false;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess)
    {
        return $isSuccess?'SUCCESS':'FAILURE';
    }
}
