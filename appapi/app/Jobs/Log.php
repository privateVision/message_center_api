<?php

namespace App\Jobs;

use App\Model\MongoDB\AppApiLog;

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
        AppApiLog::insert($this->content);
    }
}