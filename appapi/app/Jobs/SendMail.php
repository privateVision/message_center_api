<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Mail;

class SendMail extends Job
{
    protected $subject;
    protected $to;
    protected $content;

    public function __construct($subject, $to, $content)
    {
        if(!is_array($to)) {
            $to = [$to];
        }

        $this->subject = $subject;
        $this->to = array_filter($to);
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(count($this->to)) {
            Mail::raw($this->content, function($mailer) {
                $mailer->to($this->to)->subject($this->subject);
            });
        }
    }
}