<?php

namespace App\Jobs;

class KafkaProducer extends Job
{
    protected $topic;
    protected $content;

    public function __construct($topic, $content)
    {
        $this->topic = $topic;
        $this->content = $content;
    }

    public function handle()
    {
        $kafka_producer = app('kafka_producer');
        $topic = $kafka_producer->newTopic($this->topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $this->content);
    }
}