<?php
namespace App\Listeners;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use App\Events\RegisterEvent;
use App\Events\LoginEvent;

class RegisterListener
{
    public function __construct()
    {

    }

    public function handle(RegisterEvent $event)
    {
        return Arr::first(Event::fire(new LoginEvent($event->ucuser)));
    }
}