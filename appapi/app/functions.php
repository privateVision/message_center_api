<?php
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Queue;
use App\Jobs\SendSMS;
use App\Jobs\OrderSuccess;
use App\Jobs\Log;
use App\Jobs\SendMail;

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
    return md5(uniqid() . rand(0, 999999) . microtime());
}

function order_success($order_id) {
    Queue::push(new OrderSuccess($order_id));
}

function send_sms($mobile, $content, $code = '') {
    Queue::push(new SendSMS($mobile, $content, $code));
}

function send_mail($subject, $to, $content) {
    Queue::push(new SendMail($subject, $to, $content));
}

function log_debug ($keyword, $content) {
    global $app;

    Queue::push(new Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'DEBUG', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' => date('Y-m-d H:i:s'),
        'content' => $content,
    ]));
}

function log_info ($keyword, $content) {
    global $app;

    Queue::push(new Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'INFO', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' => date('Y-m-d H:i:s'),
        'content' => $content,
    ]));
}

function log_warning ($keyword, $content) {
    global $app;

    Queue::push(new Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'WARNING', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' => date('Y-m-d H:i:s'),
        'content' => $content,
    ]));
}

function log_error ($keyword, $content) {
    global $app;

    Queue::push(new Log([
        'keyword' => $keyword,
        'mode' => PHP_SAPI,
        'level' => 'ERROR', 
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'datetime' => date('Y-m-d H:i:s'),
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

