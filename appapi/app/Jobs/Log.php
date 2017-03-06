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
/*
        // --------------
        $rk = new \RdKafka\Producer();
        $rk->setLogLevel(LOG_DEBUG);
        $rk->addBrokers("127.0.0.1");
*/
        $kafka_producer = app('kafka_producer');
        $topic = $kafka_producer->newTopic("test");
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, 'test message');
        // ---------------

        AppApiLog::insert($this->content);
    }
}