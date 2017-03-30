<?php

namespace App\Providers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('kafka_producer', function () {
            $kafka_producer = new \RdKafka\Producer();
            $kafka_producer->setLogLevel(LOG_DEBUG);
            $kafka_producer->addBrokers(env('KAFKA_SERVER'));
            return $kafka_producer;
        });

        // 加载配置
        $this->app->configure('common');
    }

    public function boot() {
        Queue::failing(function ($connection, $job, $data) {

        });
    }
}