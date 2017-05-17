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

    public function __call($method, $parameters) {
        if($method === 'tableSlice') {
            $this->slice = $parameters[0];
            return $this;
        }

        /**
         * form_cache(value) = find(value)
         * form_cache_field(value) = where(field, value)
         */
        if(substr($method, 0, 10) === 'from_cache') {
            $value = $parameters[0];
            if($method !== 'from_cache') {
                $field = substr($method, 11);
                return $this->newQuery()->where($field, $value)->first();
            } else {
                return $this->find(...$parameters);
            }

            return null;
        }

        return parent::__call($method, $parameters);
    }

    public static function boot() {
        parent::boot();
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
        return $this->save();
    }

    /**
     * 更新缓存
     * @return [type] [description]
     */
    public function updateCache() {
        return $this;
    }

    /**
     * 删除缓存
     * @return [type] [description]
     */
    public function deleteCache() {
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