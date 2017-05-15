<?php
namespace App\Model\MongoDB;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
	protected $connection = 'mongodb';

    /**
     * 异步执行save
     * @return [type] [description]
     */
    public function asyncSave() {
        async_query($this);
        return $this;
    }
}