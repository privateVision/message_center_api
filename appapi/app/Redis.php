<?php
namespace App;
use Illuminate\Support\Facades\Redis as BaseRedis;

class Redis extends BaseRedis {

	/**
	 * 自旋锁，如果锁被占用则等待，直到解锁
	 * @param string $key 锁的key
	 * @param string $handler 加锁后的处理函数
	 * @param int $expire 锁过期时间，单位：秒
	 * @param int $usleep 如果发现被锁，等待多少us后再次尝试加锁
	 * @throws Exception
	 * @return mixed
	 */
    public static function spin_lock($key, $handler, $expire = 120, $usleep = 100000) {
        while(!BaseRedis::set($key, 1, 'EX', $expire, 'NX')) usleep($usleep);

        try {
            $result = $handler();
        } catch (\Exception $e) {
            BaseRedis::del($key);
            throw $e;
        }
       
        BaseRedis::del($key);
        return $result;
    }

    /**
     * 互斥锁，如果锁被占用则直接返回
     * @param  $key string 锁的key
     * @param  $closure Closure 如果锁未被占用则调用此方法
     * @param  $onlocked 如果锁被占用则调用此方法
     * @param  $expire 锁的过期时间
     */
    public static function mutex_lock($key, $handler, $onlocked_handler = null, $expire = 120) {
        if(BaseRedis::set($key, 1, 'EX', $expire, 'NX')) {
            try {
                $handler();
                BaseRedis::del($key);
            } catch (\Exception $e) {
                BaseRedis::del($key);
                throw $e;
            }
        } elseif(is_callable($onlocked_handler)) {
            $onlocked_handler();
        }
    }
}