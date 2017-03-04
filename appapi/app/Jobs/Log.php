<?php

namespace App\Jobs;

use App\Model\MongoDB\AppApiLog;
use Illuminate\Support\Facades\Mail;

class Log extends Job
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->content['level'] === 'ERROR') {
            send_mail('SDK接口调用错误', env('ALARM_MAILS'), json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        AppApiLog::insert($this->content);
    }
}