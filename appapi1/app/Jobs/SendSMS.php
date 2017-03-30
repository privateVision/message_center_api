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
                Redis::setex(sprintf('sms_%s_%s', $this->mobile, $this->code), 1800, 1);
            }

            return ;
        }

        if($this->attempts() >= 10) return;

        $data = [
            'apikey' => $this->smsconfig['apikey'],
            'mobile' => $this->mobile,
            'text' => $this->content,
        ];

        $res = http_request($this->smsconfig['sender'], $data);

        log_info('sendsms', ['req' => $data, 'res' => $res]);

        if(!$res) {
            return $this->release(5);
        }

        $res = json_decode($res, true);
        if(!$res) {
            return $this->release(5);
        }

        if($res['code'] == 0) {
            $SMSRecord = new SMSRecord;
            $SMSRecord->mobile = $this->mobile;
            $SMSRecord->content = $this->content;
            $SMSRecord->code = $this->code;
            $SMSRecord->date = date('Ymd');
            $SMSRecord->hour = date('G');
            $SMSRecord->save();

            if($this->code) {
                Redis::setex(sprintf('sms_%s_%s', $this->mobile, $this->code), 1800, 1);
            }
        } else {
            return $this->release(5);
        }
    }
}