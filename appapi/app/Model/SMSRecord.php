<?php
namespace App\Model;

class SMSRecord extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

	protected $table = 'sms_record';

    public static function verifyCode($mobile, $code) {
        $sms = static::where('mobile', $mobile)->where('code', $code)->orderBy('created_at', 'desc')->first();
        if(!$sms) {
            return null;
        }

        $t = strtotime($sms->sendTime);

        if((time() - $t) > 1800) {
            return null;
        }

        return $sms;
    }
}