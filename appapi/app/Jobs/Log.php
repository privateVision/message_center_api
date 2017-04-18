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
            send_mail('SDK接口调用错误', explode('|', env('alarm_emails')), json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $logfile = env('log_path') . str_replace('-', '', substr($this->content['datetime'], 0, 10)) . '.log';

        $text = sprintf("%s %s.%d[%s] %s [%s]%s %s\n", 
            $this->content['datetime'], 
            $this->content['ip'], 
            $this->content['pid'], 
            $this->content['mode'], 
            $this->content['level'], 
            $this->content['keyword'], 
            $this->content['desc'], 
            json_encode($this->content['content'], JSON_UNESCAPED_UNICODE)
        );

        error_log($text, 3, $logfile);
    }
}