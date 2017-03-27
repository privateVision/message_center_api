<?php
namespace App;
use Illuminate\Support\Facades\Redis as BaseRedis;

class Redis extends BaseRedis {

    // http://doc.redisfans.com/
    // STR String
    // H   Hash
    // L   List
    // S   Set 集合，以S开头的命令
    // SS  SortedSet 有序集合，以Z开头的命令
    // P   Pub 发布
    const KSTR_SMS = 'sms_%s_%s';                   // 发短信记录：%s:手机号码,%s:验证码
    const KSTR_ORDER_SUCCESS_LOCK = 'ol_%s';        // 订单成功处理锁：%s:order_id
    const KH_USERSUB_NUM = 'u_sub_n';               // 用户小号数量

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