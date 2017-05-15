<?php
namespace App\Http\Controllers\Callback;

use Illuminate\Http\Request;
use App\Model\YunpianCallback;
use Illuminate\Support\Facades\Config;

class YunpianController extends \App\Controller {
    
    public function RequestAction(Request $request) {
        try {
            $sms_reply = $request->input('sms_reply');

            log_info('YunpianCallback', $sms_reply);

            $sms_reply = @json_decode(urldecode($sms_reply), true);
            if(!$sms_reply) {
                return 'FAILURE';
            }
            
            log_info('YunpianCallback', $sms_reply);

            $sign = $sms_reply['_sign'];
            unset($sms_reply['_sign']);
            ksort($sms_reply);

            $data = [];
            foreach ($sms_reply as $k=>$v){
            	$data[] = trim($v, ' ');
            }

            $data[] = config('common.smsconfig.apikey');

            $str = implode(',', $data);

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
        }catch(\Exception $e){
            log_info('YunpianCallback', $e->getMessage());
            return 'FAILURE';
        }
    }
}