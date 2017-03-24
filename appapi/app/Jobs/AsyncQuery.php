<?php
namespace App\Jobs;

class AsyncQuery extends Job
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $model = unserialize($this->model);
        $model->save();
    }
}