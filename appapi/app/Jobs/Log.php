<?php

namespace App\Jobs;

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
        error_log($this->content. "\n", 3, );
    }
}