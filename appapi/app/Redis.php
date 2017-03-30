<?php
namespace App;
use Illuminate\Support\Facades\Redis as BaseRedis;

class Redis extends BaseRedis {

    public static function spin_lock($key, $closeure, $expire = 120, $usleep = 200000) {
        while(!BaseRedis::set($key, 1, 'EX', $expire, 'NX')) usleep($usleep);

        try {
            $result = $closeure();
        } catch (\Exception $e) {
            BaseRedis::del($key);
            throw $e;
        }
       
        BaseRedis::del($key);
        return $result;
    }

    public static function mutex_lock($key, $closeure, $expire = 120) {
        if(BaseRedis::set($key, 1, 'EX', $expire, 'NX')) {
            try {
                $closeure();
            } catch (\Exception $e) {
                BaseRedis::del($key);
                throw $e;
            }
           
            BaseRedis::del($key);
        }

        return true;
    }
}