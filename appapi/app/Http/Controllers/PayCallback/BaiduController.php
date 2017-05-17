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

    protected function getTradeOrderNo($data, $order)
    {
        return $data['OrderSerial'];
    }

    protected function verifySign($data, $order)
    {
        $result = ProceduresExtend::where('third_appid', $data['AppID'])->first();

        return $result?true:false;
    }

    protected function handler($data, $order)
    {
        $content = base64_decode($data['Content']);

        if(!isset($content['OrderStatus'])||!$content['OrderStatus']){
            return false;
        }else{
            return true;
        }
    }

    protected function onComplete($data, $order, $isSuccess)
    {
        $result = ProceduresExtend::where('third_appid', $data['AppID'])->first();

        $sign = md5($data['AppID'].$data['OrderSerial'].$data['CooperatorOrderSerial'].$data['Content'].$result['third_appsecret']);

        return json_encode([
            'AppID'=>$data['AppID'],
            'ResultCode'=>$isSuccess?1:0,
            'ResultMsg'=>'',
            'Sign'=>$sign
        ]);
    }
}
