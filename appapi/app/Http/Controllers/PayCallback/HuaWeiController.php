<?php

namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\ProceduresExtend;

class HuaWeiController extends Controller
{
    protected function getData(Request $request)
    {
        $request = file_get_contents("php://input");

        $elements = explode('&', $request);
        $valueMap = array();
        foreach ($elements as $element)
        {
            $single = explode('=', $element);
            $valueMap[$single[0]] = $single[1];
        }

        if(null !== $valueMap["sign"])
        {
            $valueMap["sign"] = urldecode($valueMap["sign"]);
        }
        if(null !== $valueMap["extReserved"])
        {
            $valueMap["extReserved"]= urldecode($valueMap["extReserved"]);
        }
        if(null !== $valueMap["sysReserved"])
        {
            $valueMap["sysReserved"] = urldecode($valueMap["sysReserved"]);
        }

        ksort($valueMap);

        return $valueMap;
    }

    protected function getOrderNo($data)
    {
        return $data['requestId'];
    }

    protected function getTradeOrderNo($data, $order)
    {
        return $data['orderId'];
    }

    protected function verifySign($data, $order)
    {
        $config = ProceduresExtend::where('third_cpid', $data['userName'])->first();

        $content = "";
        $i = 0;
        foreach($data as $key=>$value)
        {
            if($key != "sign" )
            {
                $content .= ($i == 0 ? '' : '&').$key.'='.$value;
            }
            $i++;
        }





        return true;
    }

    protected function handler($data, $order, $order_extend)
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
        $result = ProceduresExtend::where('third_appid', $data['AppID'])->first()->toArray;

        $sign = md5($data['AppID'].$data['OrderSerial'].$data['CooperatorOrderSerial'].$data['Content'].$result['third_appsecret']);

        return json_encode([
            'AppID'=>$data['AppID'],
            'ResultCode'=>$isSuccess?1:0,
            'ResultMsg'=>'',
            'Sign'=>$sign
        ]);
    }
}
