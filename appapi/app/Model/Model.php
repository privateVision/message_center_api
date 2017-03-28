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

    protected $is_cache_save = false;

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
                        $data = json_decode($data, true);
                        $this->forceFill($data);
                        $this->original = $data;
                        $this->exists = true;
                        $this->is_cache_save = true;
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

                    $data->is_cache_save = true;
                    return $data;
                }
            } else {
                $rediskey_2 = $this->table .'_'. $value;
                $data = Redis::get($rediskey_2);
                if($data) {
                    $data = json_decode($data, true);
                    $this->forceFill($data);
                    $this->original = $data;
                    $this->exists = true;
                    $this->is_cache_save = true;
                    return $this;
                }

                $data = $this->find(...$parameters);
                if($data) {
                    Redis::set($rediskey_2, json_encode($data), 'EX', cache_expire_second());
                    $data->is_cache_save = true;
                    return $data;
                }
            }

            return null;
        }

        return parent::__call($method, $parameters);
    }

    public static function boot() {
        parent::boot();

        static::created(function($entry) {
            if($entry->is_cache_save) {
                $rediskey_2 = $entry->table .'_'. $entry->getKey();
                Redis::set($rediskey_2, json_encode($entry), 'EX', cache_expire_second());
            }
        });
    
        static::updated(function($entry) {
            if($entry->is_cache_save) {
                $rediskey_2 = $entry->table .'_'. $entry->getKey();
                Redis::set($rediskey_2, json_encode($entry), 'EX', cache_expire_second());
            }
        });
    }

    /**
     * 延迟写入，同一个Model多次调用save方法只会执行一次
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    public function delaySave() {
        if(!$this->getKey()) {
            return parent::save();
        }

        $this->is_delay_save = true;

        return $this;
    }

    /**
     * 异步执行save
     * @return [type] [description]
     */
    public function asyncSave() {
        async_query($this);
        return $this;
    }

    public function saveAndCache() {
        $this->is_cache_save = true;
        return $this->save();
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