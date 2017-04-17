<?php
namespace App\Http\Controllers\Callback;

use Illuminate\Http\Request;
use App\Model\YunpianCallback;
use Illuminate\Support\Facades\Config;

class YunpianController extends \App\Controller
{
    public function RequestAction(Request $request) {

        try {

            $sms_reply = $request->input('sms_reply');



            $sms_reply = @json_decode($sms_reply, true);
            if(!$sms_reply) {
                return 'SUCCESS';
            }

            $sign = $sms_reply['_sign'];
            unset($sms_reply['_sign']);
            ksort($sms_reply);

            $dat = [];
            foreach ( $sms_reply as $k=>$v){
                $dat[$k] = trim(urldecode($v)," ");
            }
           $dat[] = config('common.smsconfig.apikey');
          //  $dat[] = '0000';
            // todo: 这里要改...
            $str = implode(',', $dat);
            log_info('YunpianCallback', $str."_____".strtolower(md5($str))."====".$sign);

            if($sign !== md5($str)) {
                return 'FAILURE';
            }
            log_info('YunpianCallback', $sms_reply);
            $yunpiansms = new YunpianCallback;
            $yunpiansms->yid = $sms_reply['id'];
            $yunpiansms->mobile = $sms_reply['mobile'];
            $yunpiansms->reply_time = $sms_reply['reply_time'];
            $yunpiansms->text = $sms_reply['text'];
            $yunpiansms->extend = $sms_reply['extend'];
            $yunpiansms->base_extend = $sms_reply['base_extend'];
            $yunpiansms->save();

            return 'SUCCESS';
        }catch(\Exception $e){
            log_info('YunpianCallback', $e->getMessage());
        }

    }
}