<?php
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendSMS;
use App\Jobs\OrderSuccess;

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

<<<<<<< HEAD
function order_notify($order) {

}

function send_sms($mobile, $content, $code = 0) {
    return Redis::lpush("queue", json_encode([
        'topic' => 'sendsms',
        'mobile' => $mobile,
        'content' => $content,
        'code' => $code
    ]));
=======
function order_success($order_id) {
    Queue::push(new OrderSuccess($order_id));
}

function send_sms($mobile, $content, $code = '') {
    Queue::push(new SendSMS($mobile, $content, $code));
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
}

function log_debug ($keyword, $content) {
    /*
    global $app;

<<<<<<< HEAD
    return Redis::lpush("queue", json_encode([
        'topic' => 'log',
        'level' => 100,
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
=======
	return Redis::lpush("sdkapi_to_kafka_queue", json_encode([
        'topic' => 'log', 
        'content' => [
            'level' => 'DEBUG', 
            'content' => $content,
            'ip' => $app->request->ip(),
            'pid' => getmypid(),
            'keyword' => $keyword,
            'content' => $content,
            'timestamp' => time(),
        ]
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
    ]));
    */
}

function log_info ($keyword, $content) {
<<<<<<< HEAD
    global $app;

    return Redis::lpush("queue", json_encode([
        'topic' => 'log',
        'level' => 200,
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
=======
    /*
	global $app;

    return Redis::lpush("sdkapi_to_kafka_queue", json_encode([
        'topic' => 'log', 
        'content' => [
            'level' => 'INFO', 
            'content' => $content,
            'ip' => $app->request->ip(),
            'pid' => getmypid(),
            'keyword' => $keyword,
            'content' => $content,
            'timestamp' => time(),
        ]
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
    ]));
    */
}

function log_warning ($keyword, $content) {
    /*
    global $app;

<<<<<<< HEAD
    return Redis::lpush("queue", json_encode([
        'topic' => 'log',
        'level' => 300,
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
=======
    return Redis::lpush("sdkapi_to_kafka_queue", json_encode([
        'topic' => 'log', 
        'content' => [
            'level' => 'WARNING', 
            'content' => $content,
            'ip' => $app->request->ip(),
            'pid' => getmypid(),
            'keyword' => $keyword,
            'content' => $content,
            'timestamp' => time(),
        ]
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
    ]));
    */
}

function log_error ($keyword, $content) {
<<<<<<< HEAD
    global $app;

    return Redis::lpush("queue", json_encode([
        'topic' => 'log',
        'level' => 400,
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
=======
    /*
	global $app;

    return Redis::lpush("sdkapi_to_kafka_queue", json_encode([
        'topic' => 'log', 
        'content' => [
            'level' => 'ERROR', 
            'content' => $content,
            'ip' => $app->request->ip(),
            'pid' => getmypid(),
            'keyword' => $keyword,
            'content' => $content,
            'timestamp' => time(),
        ]
>>>>>>> 64584c582a36ce8763d45931925d90ee79a41b77
    ]));
    */
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