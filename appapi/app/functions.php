<?php
use App\Redis;
use Illuminate\Support\Facades\Queue;

// 设置redis的key在过期时间
// 1. 一个用户的所有数据应该同时过期
// 2. 避开在服务器高压状态时过期
function cache_expire_second() {
    return 86400;
}

/**
 * 3DES加密
 * @param  [type] $data [description]
 * @param  [type] $key  [description]
 * @return [type]       [description]
 */
function encrypt3des($data, $key = null) {
    if(empty($key)) {
        $key = env('API_3DES_KEY');
    }

    return \App\Crypt3DES::encrypt($data, $key);
}

/**
 * 3DES解密
 * @param  [type] $data [description]
 * @param  [type] $key  [description]
 * @return [type]       [description]
 */
function decrypt3des($data, $key = null) {
    if(empty($key)) {
        $key = env('API_3DES_KEY');
    }

    return \App\Crypt3DES::decrypt($data, $key);
}

/**
 * 解析身份证号码
 * @param  [type] $card_id [description]
 * @return [type]          [description]
 */
function parse_card_id($card_id) {
    $len = strlen($card_id);

    if($len !== 15 && $len !== 18) return false;

    $provinces = [
        11 => '北京', 12 => '天津', 13 => '河北', 14 => '山西', 15 => '内蒙古', 
        21 => '辽宁', 22 => '吉林', 23 => '黑龙江', 
        31 => '上海', 32 => '江苏', 33 => '浙江', 34 => '安徽', 35 => '福建', 36 => '江西', 37 => '山东', 
        41 => '河南', 42 => '湖北', 43 => '湖南', 44 => '广东', 45 => '广西', 46 => '海南', 
        50 => '重庆', 51 => '四川', 52 => '贵州', 53 => '云南', 54 => '西藏', 
        61 => '陕西', 62 => '甘肃', 63 => '青海', 64 => '宁夏', 65 => '新疆', 
        71 => '台湾', 81 => '香港', 82 => '澳门', 91 => '国外',
    ];
    
    $province = @$provinces[substr($card_id, 0, 2)];

    if(!$province) return false;

    if(strlen($card_id) === 18) {
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $mod = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        $n = 0;
        for($i = 0; $i < strlen($card_id) - 1; $i++) {
            $char = $card_id[$i];
            $n += intval($char) * $factor[$i];
        }

        $code = $mod[$n % 11];

        if(strtoupper(substr($card_id, 17, 1)) !== $code) return false;

        $birthday = substr($card_id, 6, 8);
        $gender = intval(substr($card_id, 16, 1)) % 2 == 1 ? 1 : 2;
    } else {
        $birthday = substr($card_id, 6, 6);
        if(intval($birthday) > 800101) return false; // 15位身份证早就没颁发了，骗纸
        $birthday = '19' . $birthday;

        $gender = intval(substr($card_id, 14, 1)) % 2 == 1 ? 1 : 2;
    }

    return ['birthday' => $birthday, 'gender' => $gender, 'province' => $province];
}

/**
 * 生成唯一用户名
 * @return [type] [description]
 */
function username() {
    $username = null;

    $chars = 'abcdefghjkmnpqrstuvwxy';
    do {
        $username = $chars[rand(0, 21)] . rand(10000, 99999999);

        $count = \App\Model\User::where('uid', $username)->count();
        if($count == 0) {
            return $username;
        }
    } while(true);
}

/**
 * 生成唯一ID，24~25位36进制
 * @param  string $prefix [description]
 * @return [type]         [description]
 */
function uuid($prefix = "") {
    return base_convert(md5($prefix . uniqid(mt_rand(), true) . microtime() . mt_rand()), 16, 36);
}

/**
 * 主要用于生成多联的KEY，24~25位36进制
 * @return [type] [description]
 */
function joinkey() {
    $key = implode('_', func_get_args());
    return base_convert(md5($key), 16, 36);
}

/**
 * 当前时间，Y-m-d H:i:s
 * @param  integer $time [description]
 * @return [type]        [description]
 */
function datetime($time = 0) {
    if($time) {
        return date('Y-m-d H:i:s', $time);
    }

    return date('Y-m-d H:i:s');
}

/**
 * 生成验证码code
 * @return [type] [description]
 */
function smscode() {
    return rand(100000, 999999);
}

/**
 * 异步执行Model::save()方法，对应Model::asyncSave
 * @param  [type] $model [description]
 * @return [type]        [description]
 */
function async_query($model) {
    Queue::push(new \App\Jobs\AsyncQuery(serialize($model)));
}

/**
 * 异步执行\App\Jobs\AsyncExecute里的方法
 * @param  [string] $method 方法名
 * @return [type]         [description]
 */
function async_execute($method) {
    $arguments = func_get_args();
    unset($arguments[0]);
    Queue::push(new \App\Jobs\AsyncExecute($method, $arguments));
}

function user_log($user, $procedure, $type, $text_format) {
    if(is_numeric($user)) {
        $user = \App\Model\User::from_cache($user);
        if(!$user) return;
    }

    if(is_numeric($procedure)) {
        $procedure = \App\Model\Procedures::from_cache($procedure);
    }

    $format_arguments = array_slice(func_get_args(), 4);

    $user_log = new \App\Model\UserLog;
    $user_log->type = $type;
    $user_log->ucid = $user->ucid;
    $user_log->mobile = $user->mobile;
    $user_log->pid = $procedure ? $procedure->pid : '';
    $user_log->pname = $procedure ? $procedure->pname : '';
    $user_log->text = count($format_arguments) ? sprintf($text_format, ...$format_arguments) : $text_format;
    $user_log->asyncSave();
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

