<?php

namespace App\Jobs;

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