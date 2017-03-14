<?php
namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\LoginEvent::class => [
            \App\Listeners\LoginListener::class,
        ],

        \App\Events\RegisterEvent::class => [
            \App\Listeners\RegisterListener::class,
        ],

        \App\Events\LogoutEvent::class => [
            \App\Listeners\LogoutListener::class,
        ],
    ];
}
