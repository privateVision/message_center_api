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
    const KSTR_REQUEST_SIGN_LOCK = 'sl_%s';         // 请求锁：%ssign
    const KSTR_SMS = 'sms_%s_%s';                   // 发短信记录：%s手机号码%s验证码
    const KSTR_ORDER_SUCCESS_LOCK = 'ol_%s';        // 订单成功处理锁：%sorder_id
    const KH_USER = 'u_%s';
    const KSTR_INCR = 'incr_%s';
}