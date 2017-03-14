<?php

namespace App\Events;

class LogoutEvent extends Event
{

    public $ucuser;

    public function __construct(\App\Model\Ucusers $ucuser)
    {
        $this->ucuser = $ucuser;
    }

    public function broadcastOn()
    {
        return [];
    }
}