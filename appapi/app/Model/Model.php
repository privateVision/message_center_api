<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Redis;

abstract class Model extends Eloquent
{
    protected $connection = 'default';

	const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $slice = null;

    /**
     * 如果值为true，则该类在析构的时候会自动执行一次save
     * @var boolean
     */
    protected $is_delay_save = false;

    /**
     * 如果值为true，则在updated、created时会更新缓存
     * @var boolean
     */
    protected $is_cache_save = false;

    protected static $_instances = [];

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function increment($column, $amount = 1, array $extra = []) {
        parent::increment($column, $amount, $extra);
        $this->updateCache();
        return $this;
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function decrement($column, $amount = 1, array $extra = [])
    {
        parent::decrement($column, $amount, $extra);
        $this->updateCache();
        return $this;
    }

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
                if(isset(static::$_instances[$rediskey_2])) return static::$_instances[$rediskey_2];

                if($rediskey_2) {
                    $data = Redis::get($rediskey_2);
                    if($data) {
                        $data = json_decode($data, true);

                        $this->forceFill($data);
                        $this->original = $data;
                        $this->exists = true;
                        $this->is_cache_save = true;

                        static::$_instances[$rediskey_2] = $this;

                        return $this;
                    }
                }

                $data = $this->newQuery()->where($field, $value)->first();
                if($data) {
                    $rediskey_2 = $this->table .'_'. $data->getKey();

                    Redis::set($rediskey_2, json_encode($data), 'EX', cache_expire_second());
                    Redis::set($rediskey_1, $rediskey_2, 'EX', cache_expire_second());

                    $data->is_cache_save = true;
                    static::$_instances[$rediskey_2] = $data;

                    return $data;
                }
            } else {
                $rediskey_2 = $this->table .'_'. $value;
                if(isset(static::$_instances[$rediskey_2])) return static::$_instances[$rediskey_2];

                $data = Redis::get($rediskey_2);
                if($data) {
                    $data = json_decode($data, true);
                    $this->forceFill($data);
                    $this->original = $data;
                    $this->exists = true;
                    $this->is_cache_save = true;

                    static::$_instances[$rediskey_2] = $this;

                    return $this;
                }

                $data = $this->find(...$parameters);
                if($data) {
                    Redis::set($rediskey_2, json_encode($data), 'EX', cache_expire_second());
                    $data->is_cache_save = true;
                    static::$_instances[$rediskey_2] = $data;
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
                $entry->updateCache();
            }
        });
    
        static::updated(function($entry) {
            if($entry->is_cache_save) {
                $entry->updateCache();
            }
        });

        static::deleting(function($entry) {
            $entry->deleteCache();
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

    /**
     * 保存数据且缓存
     * @return [type] [description]
     */
    public function saveAndCache() {
        $this->is_cache_save = true;
        return $this->save();
    }

    /**
     * 更新缓存
     * @return [type] [description]
     */
    public function updateCache() {
        $rediskey_2 = $this->table .'_'. $this->getKey();
        Redis::set($rediskey_2, json_encode($this), 'EX', cache_expire_second());
        static::$_instances[$rediskey_2] = $this;

        return $this;
    }

    /**
     * 删除缓存
     * @return [type] [description]
     */
    public function deleteCache() {
        $rediskey_2 = $this->table .'_'. $this->getKey();
        Redis::del($rediskey_2);
        
        unset(static::$_instances[$rediskey_2]);

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