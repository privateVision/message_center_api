<?php
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

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

function order_notify($order) {
    
}

function send_sms($mobile, $content, $code = 0) {
	return Redis::lpush("queue", json_encode([
        'topic' => 'sendsms', 
        'mobile' => $mobile, 
        'content' => $content, 
        'code' => $code
    ]));
}

function log_debug ($keyword, $content) {
    global $app;

	return Redis::lpush("queue", json_encode([
        'topic' => 'log', 
        'level' => 100, 
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
    ]));
}

function log_info ($keyword, $content) {
	global $app;

    return Redis::lpush("queue", json_encode([
        'topic' => 'log', 
        'level' => 200, 
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
    ]));
}

function log_warning ($keyword, $content) {
    global $app;

    return Redis::lpush("queue", json_encode([
        'topic' => 'log', 
        'level' => 300, 
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
    ]));
}

function log_error ($keyword, $content) {
	global $app;

    return Redis::lpush("queue", json_encode([
        'topic' => 'log', 
        'level' => 400, 
        'content' => $content,
        'ip' => $app->request->ip(),
        'pid' => getmypid(),
        'keyword' => $keyword,
        'content' => $content
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