<?php
namespace App\Jobs;


use App\Model\SMS;
use Illuminate\Http\Request;

class SendSMS extends Job
{
    protected $mobile;

    protected $text;

    protected $code;

    public function __construct($mobile, $text, $code = '')
    {
        $this->mobile = $mobile;
        $this->text = $text;
        $this->code = $code;
    }

    public function handle()
    {
        $sms = new SMS;
        $sms->mobile = $this->mobile;
        $sms->authCode = $this->text;
        $sms->acode = $this->code;
        $sms->save();
        return ;
        if($this->attempts() >= 10) return;

        $config = config('common.yunpian');

        $data = [
            'apikey' => $config['apikey'],
            'mobile' => $this->mobile,
            'text' => $this->text,
        ];

        $res = http_request($config['sender'], $data);

        log_info('sendsms', ['req' => $data, 'res' => $res]);

        if(!$res) {
            return $this->release(5);
        }

        $res = json_decode($res, true);
        if(!$res) {
            return $this->release(5);
        }

        if($res['code'] == 0) {
            $sms = new SMS;
            $sms->mobile = $this->mobile;
            $sms->authCode = $this->text;
            $sms->acode = $this->code;
            $sms->save();
        } else {
            return $this->release(5);
        }
    }
}

