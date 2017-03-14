<?php
namespace App\Http\Controllers\Pub;

use Illuminate\Http\Request;
use App\Model\YunpianCallback;
use Illuminate\Support\Facades\Config;

class YunpianController extends \App\Controller
{
    public function CallbackAction(Request $request) {
        $sms_reply = $request->input('sms_reply');

        log_info('YunpianCallback', $request->all());

        $sms_reply = @json_decode($sms_reply, true);
        if(!$sms_reply) {
            return 'SUCCESS';
        }

        $sign = $sms_reply['_sign'];
        unset($sms_reply['_sign']);
        ksort($sms_reply);

        // todo: 这里要改...
        $str = implode(',', $sms_reply) .','. config('common.yunpian.apikey');
        if($sign !== md5($str)) {
            return 'FAILURE';
        }

        $yunpiansms = new YunpianCallback;
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