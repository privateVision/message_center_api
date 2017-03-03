<?php
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendSMS;
use App\Jobs\OrderSuccess;
use App\Jobs\Log;

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