<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\YunpianSms;

class YunpianController extends \App\Controller
{
    public function CallbackAction(Request $request) {
        $sms_reply = $request->input('sms_reply');
        $sms_reply = @json_decode($sms_reply, true);
        if(!$sms_reply) {
            return 'SUCCESS';
        }

        $sign = $sms_reply['_sign'];
        unset($sms_reply['_sign']);
        ksort($sms_reply);

        $str = implode(',', $sms_reply) .','. env('YUNPIAN_APPKEY');
        if($sign !== md5($str)) {
            return 'FAILURE';
        }

        $yunpiansms = new YunpianSms;
        $yunpiansms->yid = $sms_reply['id'];
        $yunpiansms->mobile = $sms_reply['mobile'];
        $yunpiansms->reply_time = $sms_reply['reply_time'];
        $yunpiansms->text = $sms_reply['text'];
        $yunpiansms->extend = $sms_reply['extend'];
        $yunpiansms->base_extend = $sms_reply['base_extend'];
        $yunpiansms->save();

        return 'SUCCESS';
    }
}