<?php

namespace App\Events;

class ExampleEvent extends Event
{

    public function __construct()
    {
        //
    }

    public function broadcastOn()
    {
        echo "广播";
        return $this;
        //return ['1'];
    }
}
