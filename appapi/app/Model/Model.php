<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Redis;

abstract class Model extends Eloquent
{
    protected $connection = 'anfanapi';

	const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $slice = null;

    protected $is_delay_save = false;

    public function __call($method, $parameters) {
        if($method === 'tableSlice') {
            $this->slice = $parameters[0];
            return $this;
        }

        if(substr($method, 0, 10) === 'from_cache') {
            $value = $parameters[0];
            if($method !== 'from_cache') {
                $field = substr($method, 11);

                $rediskey_1 = $this->table .'_'. $field .'_'. $value;
                $rediskey_2 = Redis::get($rediskey_1);

                if($rediskey_2) {
                    $k_2 = true;
                    $data = Redis::get($rediskey_2);
                    if($data) {
                        $this->forceFill(json_decode($data, true));
                        return $this;
                    }
                }

                $data = $this->newQuery()->where($field, $value)->first();
                if($data) {
                    $rediskey_2 = $this->table .'_'. $data->getKey();
                    Redis::set($rediskey_2, json_encode($data), 'EX', cache_expire_second());
                    if(!isset($k_2)) {
                        Redis::set($rediskey_1, $rediskey_2, 'EX', cache_expire_second());
                    }

                    return $data;
                }
            } else {
                $rediskey_2 = $this->table .'_'. $value;
                $data = Redis::get($rediskey_2);
                if($data) {
                    $this->forceFill(json_decode($data, true));
                    return $this;
                }

                $data = $this->find(...$parameters);
                if($data) {
                    Redis::set($rediskey_2, json_encode($data), 'EX', cache_expire_second());
                    return $data;
                }
            }

            return null;
        }

        return parent::__call($method, $parameters);
    }

    public static function boot() {
        parent::boot();
    
        static::updated(function($entry) {
            $rediskey_2 = $entry->table . $entry->getKey();
            Redis::set($rediskey_2, serialize($entry), 'XX');
        });

        static::deleted(function($entry) {
            $rediskey_2 = $entry->table . $entry->getKey();
            Redis::del($rediskey_2);
        });
    }

    /**
     * 延迟写入，同一个Model多次调用save方法只会执行一次
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    public function delaySave(array $options = []) {
        if(!$this->getKey()) {
            return parent::save($options);
        }

        $this->is_delay_save = true;

        return $this;
    }

    public function save(array $options = []) {
        $this->is_delay_save = false;
        return parent::save($options);
    }

    public function __destruct() {
        if($this->is_delay_save) {
            $this->save();
        }
    }
}