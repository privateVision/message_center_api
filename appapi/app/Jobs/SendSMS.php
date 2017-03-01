<?php

namespace App\Jobs;

<<<<<<< HEAD
class SendSMS extends Job
{
    protected $mobile;
    protected $content;
    protected $code;

    public function __construct($mobile, $content, $code = 0)
    {
        $this->mobile = $mobile;
        $this->content = $content;
        $this->code = $code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            'apikey' => env('YUNPIAN_APPKEY'),
            'mobile' => "15021829660",//$this->mobile,
            'text' => $this->content,
        ];

        $res = http_request('https://sms.yunpian.com/v2/sms/single_send.json', $data);
        log_debug('yunpian_sendsms', ['requestData' => $data, 'response' => $res]);

        return 0;
    }
}
=======
use App\Model\SMS;

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
        if($this->attempts() >= 10) return;

        $config = config('common.yunpian');

        $data = [
            'apikey' => $config['apikey'],
            'mobile' => $this->mobile,
            'text' => $this->text,
        ];

        $res = http_request($config['sender'], $data);
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
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
