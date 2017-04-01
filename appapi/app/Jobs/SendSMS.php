<?php
namespace App\Jobs;
use App\Model\SMSRecord;
use App\Redis;

class SendSMS extends Job
{
    protected $smsconfig;
    protected $mobile;
    protected $content;
    protected $code;

    public function __construct($smsconfig, $mobile, $content, $code = 0)
    {
        $this->smsconfig = $smsconfig;
        $this->mobile = $mobile;
        $this->content = $content;
        $this->code = $code;
    }

    public function handle()
    {
        if(env('APP_DEBUG', true)) {
            $SMSRecord = new SMSRecord;
            $SMSRecord->mobile = $this->mobile;
            $SMSRecord->content = $this->content;
            $SMSRecord->code = $this->code;
            $SMSRecord->date = date('Ymd');
            $SMSRecord->hour = date('G');
            $SMSRecord->save();

            if($this->code) {
                Redis::set(sprintf('sms_%s_%s', $this->mobile, $this->code), 1, 'EX', 900);
            }

            return ;
        }

        if($this->attempts() >= 10) return;

        $data = [
            'apikey' => $this->smsconfig['apikey'],
            'mobile' => $this->mobile,
            'text' => $this->content,
        ];

        $restext = http_request($this->smsconfig['sender'], $data);

        log_debug('sendsms', ['req' => $data, 'res' => $restext]);

        if(!$restext) {
            return $this->release(5);
        }

        $res = json_decode($restext, true);
        if(!$res) {
            return $this->release(5);
        }

        if(@$res['code'] == 0) {
            $SMSRecord = new SMSRecord;
            $SMSRecord->mobile = $this->mobile;
            $SMSRecord->content = $this->content;
            $SMSRecord->code = $this->code;
            $SMSRecord->date = date('Ymd');
            $SMSRecord->hour = date('G');
            $SMSRecord->save();

            if($this->code) {
                Redis::set(sprintf('sms_%s_%s', $this->mobile, $this->code), 1, 'EX', 900);
            }
        } else {
            log_error('sendsms', ['req' => $data, 'res' => $restext]);
            return $this->release(5);
        }
    }
}