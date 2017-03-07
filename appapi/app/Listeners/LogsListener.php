<?php

namespace App\Listeners;

use App\Events\ExampleEvent;
use App\Model\MongoDB\AccountLog;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery\CountValidator\Exception;

class LogsListener
{

    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(ExampleEvent $event)
    {
        //
       $data = $event->broadcastOn();
        try {               AccountLog::created($data);
            $account_log = AccountLog::create($data);
        }catch(Exception $e){

        }
        echo "消息duilie";
    }
}