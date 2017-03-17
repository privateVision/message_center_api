<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Redis;

abstract class Model extends Eloquent
{
	protected $connection = 'anfanapi';

	const CREATED_AT = null;
    const UPDATED_AT = null;
/*
    public static function __callStatic($method, $parameters) {
        if(substr($method, 0, 4) === 'find') {
            $instance = new static;

            $field = substr($method, 5);
            if(!$field) {
                $field = $instance->getKeyName();
            }

            $where = $parameters[0];
            $data = $instance->where($field, $where)->first();

            if($data) {
                Redis::setex(sprintf('table_%s_%s', $data->getTable(), $data->getKey()), 86400, serialize($data));
            }

            return $data;
        }

        //return parent::__callStatic($method, ...$parameters);
    }
*/
}