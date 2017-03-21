<?php
use App\Redis;
use Illuminate\Support\Facades\Queue;

// 设置redis的key在过期时间
// 1. 一个用户的所有数据应该同时过期
// 2. 避开在服务器高压状态时过期
function cache_expire_second() {
    return 86400;
}

function encrypt3des($data, $key = null) {
    if(empty($key)) {
        $key = env('API_3DES_KEY');
    }

    return \App\Crypt3DES::encrypt($data, $key);
}

function decrypt3des($data, $key = null) {
    if(empty($key)) {
        $key = env('API_3DES_KEY');
    }

    return \App\Crypt3DES::decrypt($data, $key);
}

function uuid() {
    return md5(uniqid(mt_rand(), true) . microtime() . mt_rand());
}

function auto_increment($type) {
    return Redis::incr(sprintf(Redis::KSTR_INCR, $type));
}

function datetime($time = 0) {
    if($time) {
        return date('Y-m-d H:i:s', $time);
    }

    return date('Y-m-d H:i:s');
}

function smscode() {
    return rand(100000, 999999);
}

function user_log($user, $type, $procedure, $text_format) {

}

function order_success($order_id) {
    Queue::push(new \App\Jobs\OrderSuccess($order_id));
}

/**
 * 发送短信
 * @param  [string] $mobile             [手机号码]
 * @param  [int or object] $app         [要发送短信的app对象或appid]
 * @param  [string] $template_id        [短信模板]
 * @param  [array] $repalce             [替换短信模板中的内容]
 * @param  [int] $code                  [短信验证码]
 * @return [null]                       []
 */
function send_sms($mobile, $app, $template_id, $repalce, $code = '') {
    if(!is_object($app)) {
        $app = config('common.apps.'.$app);
        if(!$app) {
            throw new \App\Exceptions\Exception('应用未授权');
        }
    }

    $SMSRecord = \App\Model\SMSRecord::where('mobile', $mobile)->where('date', date('Ymd'))->where('hour', date('G'))->orderBy('created_at', 'desc')->get();

    if(!env('APP_DEBUG')) {
        if(count($SMSRecord) >= $app->sms_hour_limit) {
            throw new \App\Exceptions\Exception(sprintf('短信发送次数超过限制，请%d分钟后再试', 60 - intval(date('i'))));
        }

        if(count($SMSRecord)) {
            $last_sms = $SMSRecord[0];
            if(time() - strtotime($last_sms->created_at) < 60) {
                throw new \App\Exceptions\Exception('短信发送过于频繁');
            }
        }
    }

    if(!isset($app->sms_template[$template_id])) {
        throw new \App\Exceptions\Exception('短信模板不存在');
    }

    if(is_array($repalce) && count($repalce)) {
        $content = str_replace(array_keys($repalce), array_values($repalce), $app->sms_template[$template_id]);
    } else {
        $content = $app->sms_template[$template_id];
    }

    Queue::push(new \App\Jobs\SendSMS($app, $mobile, $content, $code));

    return $content;
}

function verify_sms($mobile, $code) {
    if(env('APP_DEBUG')) {
        return true;
    }

    return Redis::get(sprintf(Redis::KSTR_SMS, $mobile, $code)) ? true : false;
}

function kafka_producer($topic, $content) {
    Queue::push(new \App\Jobs\KafkaProducer($topic, $content));
}

function send_mail($subject, $to, $content) {
    Queue::push(new \App\Jobs\SendMail($subject, $to, $content));
}

function log_debug ($keyword, $content) {
    global $app;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'DEBUG', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' =>datetime(),
        'content' => $content,
    ]));
}

function log_info ($keyword, $content) {
    global $app;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'INFO', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' =>datetime(),
        'content' => $content,
    ]));
}

function log_warning ($keyword, $content) {
    global $app;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'WARNING', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' =>datetime(),
        'content' => $content,
    ]));
}

function log_error ($keyword, $content) {
    global $app;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'ERROR', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' =>datetime(),
        'content' => $content,
    ]));
}

function http_request($url, $data, $is_post = true) {
    $data = http_build_query($data);
    if(!$is_post) {
        $url = strpos($url, '?') == -1 ? ($url .'?'. $data) : ($url .'&'. $data);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($is_post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}
//监测当前的格式
function check_name($username,$len = 32){
    if(!preg_match("/^[\w\_\-\.\@\:]+$/",$username) || strlen($username) > $len ) return false;
    return true;
}

//监测的手机格式的判定
function check_mobile($mobile){
    if(!preg_match("/^1[34578]\d{9}$/",$mobile)) return false;
    return true;
}

//监测短信验证码
function check_code($code,$len=6){
    if(!preg_match("/^\d{$len}$/",$code)) return false;
    return true;
}

//检查当前的金额 12.34 or 12  参数一金额 参数二监测小数点后的数据 bug 没法控制全部是0
function check_money($money,$del=4){
    if(!preg_match("/^(\d{0,8}).?(?=\d+)(.\d{0,$del})?$/",$money)) return false;
    return true;
}

