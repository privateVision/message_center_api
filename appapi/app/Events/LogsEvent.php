<?php

namespace App\Events;

class LogsEvent extends Event
{
    public $data;
    public function __construct($data=[])
    {
        //
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return $this->data;//return ['1'];
    }
}
