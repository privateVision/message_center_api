<?php
use App\Redis;
use Illuminate\Support\Facades\Queue;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Http\Client;

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
 * 如果客户端以HTTPS请求接口，则返回的一些涉及到URL的参数也改为HTTPS
 * @param $url
 * @return string
 */
function httpsurl($url) {
    if(app('request')->getScheme() == 'https' && substr($url, 0, 5) == 'http:') {
        return 'https:' . substr($url, 5);
    }

    return $url;
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

function upload_to_cdn($filename, $filepath, $is_delete = true) {
    /*
    200 操作执行成功。
    298 部分操作执行成功。
    400 请求报文格式错误。包括上传时，上传表单格式错误
    401 认证授权失败。包括密钥信息不正确；数字签名错误；授权已超时。
    403 拒绝访问。防盗链屏蔽的结果
    404 资源不存在。包括空间资源不存在；镜像源资源不存在。
    405 请求方式错误。主要指非预期的请求方式。
    406 上传的数据 CRC32 校验错误。
    413 请求资源大小大于指定的最大值。
    419 用户账号被冻结。
    478 镜像回源失败。主要指镜像源服务器出现异常。
    502 错误网关。
    503 服务端不可用。
    504 服务端操作超时。
    573 单个资源访问频率过高。
    579 上传成功但是回调失败。包括业务服务器异常；七牛服务器异常；服务器间网络异常。
    599 服务端操作失败。
    608 资源内容被修改。
    612 指定资源不存在或已被删除。
    614 目标资源已存在。
    630 已创建的空间数量达到上限，无法创建新空间。
    631 指定空间不存在。
    640 调用列举资源(list)接口时，指定非法的marker参数。
    701 在断点续上传过程中，后续上传接收地址不正确或ctx信息已过期。
    */
    $config = config('common.storage_cdn.qiniu');

    $auth = new Auth($config['access_key'], $config['secret_key']);

    $delete = function() use($auth, $config, $filename) {
        $bucketMgr = new BucketManager($auth);

        $result = $bucketMgr->delete($config['bucket'], $filename);
        if($result && $result->code() != 612 && $result->code() != 200) {
            log_error('cdn_delete_error', ['code' => $result->code(), 'message' => $result->message()]);
            throw new \App\Exceptions\Exception(trans('messages.upload_fail_info', ['fail_info' => $result->message()]));
        }

    };

    if($is_delete) $delete();

    $update = function() use($auth, $config, $filename, $filepath, $delete) {
        $uploadMgr = new UploadManager();
        $token = $auth->uploadToken($config['bucket']);

        list($ret, $err) = $uploadMgr->putFile($token, $filename, $filepath);

        if($err) {
            if($err->code() != 614) {
                log_error('cdn_update_error', ['code' => $ret->code(), 'message' => $ret->message()]);
                trans('messages.upload_fail_info', ['fail_info' => $ret->message()]);
            }

            $delete();
           // $update();
        }

        return $ret;
    };

    $update();

    return $config['base_url'] . $filename;
}

//更新七牛缓存文件
function updateQnCache($url){
    $data = ["urls"=>[$url]];
    $config = config('common.storage_cdn.qiniu');

    $auth = new Auth($config['access_key'], $config['secret_key']);
    $headers = $auth->authorization('http://fusion.qiniuapi.com/v2/tune/refresh');
    $headers['Content-Type'] = 'application/json';
    $res = Client::post('http://fusion.qiniuapi.com/v2/tune/refresh', json_encode($data), $headers);
    return $res;
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

        $count = \App\Model\Ucuser::where('uid', $username)->count();
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
    return md5_36($prefix . uniqid(mt_rand(), true) . microtime() . mt_rand());
}

function md5_36($str) {
    return base_convert(md5($str), 16, 36);
}

/**
 * 主要用于生成多联的KEY，24~25位36进制
 * @return string md5 to 36
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
        $user = \App\Model\Ucuser::from_cache($user);
        if(!$user) return;
    }

    if(is_numeric($procedure)) {
        $procedure = \App\Model\Procedures::from_cache($procedure);
    }

    $format_arguments = array_slice(func_get_args(), 4);

    $user_log = new \App\Model\UcuserLog;
    $user_log->type = $type;
    $user_log->ucid = $user->ucid;
    $user_log->mobile = $user->mobile;
    $user_log->pid = isset($procedure) ? $procedure->pid : '';
    $user_log->pname = isset($procedure) ? $procedure->pname : '';
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
function send_sms($mobile, $pid, $template_id, $repalce, $code = '') {
    $smsconfig = config('common.smsconfig');

    $code = trim($code);

    if(!isset($smsconfig['template'][$template_id])) {
        throw new \App\Exceptions\Exception(trans('messages.sms_template_not_exists'));
    }

    if(is_array($repalce) && count($repalce)) {
        $content = str_replace(array_keys($repalce), array_values($repalce), $smsconfig['template'][$template_id]);
    } else {
        $content = $smsconfig['template'][$template_id];
    }

    $sendsms_jobs = new \App\Jobs\SendSMS($smsconfig, $template_id, $mobile, $content, $code);
    $sendsms_jobs->verify($mobile, $template_id, $content, $code != '');

    Queue::push($sendsms_jobs);

    return $content;
}

function verify_sms($mobile, $code) {
    if(env('APP_DEBUG')) {
        return true;
    }

    $rediskey = sprintf('sms_%s_%s', $mobile, $code);
    $result = Redis::get($rediskey);
    if($result) {
        Redis::expire($rediskey, 60); // 如果验证码验证成功，将修改有效期
        return true;
    }

    return false;
}

function kafka_producer($topic, $content) {
    Queue::push(new \App\Jobs\KafkaProducer($topic, $content));
}

function send_mail($subject, $to, $content) {
    Queue::push(new \App\Jobs\SendMail($subject, $to, $content));
}

function log_debug ($keyword, $content, $desc = '') {
    if(env('log_level') > 0) return;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'desc' => $desc,
        'mode' => PHP_SAPI,
        'level' => 'DEBUG', 
        'ip' => getClientIp(),
        'pid' => getmypid(),
        'datetime' =>datetime() .'.'. substr(microtime(), 2, 6),
        'content' => $content,
    ]));
}

function log_info ($keyword, $content, $desc = '') {
    if(env('log_level') > 1) return;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'desc' => $desc,
        'mode' => PHP_SAPI,
        'level' => 'INFO', 
        'ip' => getClientIp(),
        'pid' => getmypid(),
        'datetime' =>datetime() .'.'. substr(microtime(), 2, 6),
        'content' => $content,
    ]));
}

function log_warning ($keyword, $content, $desc = '') {
    if(env('log_level') > 2) return;

    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'desc' => $desc,
        'mode' => PHP_SAPI,
        'level' => 'WARNING', 
        'ip' => getClientIp(),
        'pid' => getmypid(),
        'datetime' =>datetime() .'.'. substr(microtime(), 2, 6),
        'content' => $content,
    ]));
}

function log_error ($keyword, $content, $desc = '') {
    Queue::push(new \App\Jobs\Log([
        'keyword' => $keyword,
        'desc' => $desc,
        'mode' => PHP_SAPI,
        'level' => 'ERROR', 
        'ip' => getClientIp(),
        'pid' => getmypid(),
        'datetime' =>datetime() .'.'. substr(microtime(), 2, 6),
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

    // is https
    if (stripos($url,"https://") !== FALSE) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    // is post
    if($is_post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //超时限制
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}

/**
 * @param $url
 * @param array $param
 * @param bool $is_post
 * @param string $code
 * @param array $header
 * @param array $cookie
 * @return array|mixed
 */
function http_curl($url, $param = array(), $is_post = true, $code = 'cd', $header = array(), $cookie = array()){
    if (is_string($param)) {
        $strPOST = $param;
    }
    else if (is_array($param) && count($param)>0) {
        $strPOST =  makeQueryString($param);
    }
    else {
        $strPOST = '';
    }

    if (!$is_post) {
        $url = strpos($url, '?') == -1 ? ($url .'?'. $strPOST) : ($url .'&'. $strPOST);
    }

    $oCurl = curl_init();
    if (stripos($url,"https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 60);
    if ($is_post) {
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    }
    if (!empty($header)) {
        curl_setopt($oCurl, CURLOPT_HTTPHEADER,$header);
    }
    if (!empty($cookie)) {
        curl_setopt($oCurl, CURLOPT_COOKIE, makeCookieString($cookie));
    }

    $Resp = curl_exec($oCurl);//运行curl
    $Err = curl_error($oCurl);

    if (false === $Resp || !empty($Err)){
        $Errno = curl_errno($oCurl);
        $Info = curl_getinfo($oCurl);
        curl_close($oCurl);

        return array(
             $code => 0,
            'rspmsg' => $Err,
            'errno' => $Errno,
            'info' => $Info,
        );
    }
    curl_close($oCurl);//关闭curl

    $res = json_decode($Resp, true);
    if (is_array($res)) {
        $res[$code] = 1;
    } else {
        $res = array($code=>1, 'rspmsg'=>'http 200 data error', 'data'=>$Resp);
    }

    return $res;

//    $resp = curl_exec($oCurl);
//    $curl_error = curl_error($oCurl);
//    $curl_errno = curl_errno($oCurl);
//    $curl_code = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
//
//    curl_close($oCurl);
//    if (empty($curl_error)) {
//        $res = json_decode($resp, true);
//        if (is_array($res)) {
//            $res[$code] = isset($res[$code])?$res[$code]:1;
//            $response = $res;
//        }
//        else if ($curl_code == 200) {
//            $response = array($code=>1, 'rspmsg'=>'http 200', 'data'=>$resp);
//        }
//        else {
//            $response = array($code=>'0', 'rspmsg'=>'http response error curl_code1:'.$curl_code.' curl_errno1:'.$curl_errno.' curl_error1:'.$curl_error.' resp:'.$resp);
//        }
//    }
//    else {
//        if ($curl_code == 200) {
//            $response = array($code=>1, 'rspmsg'=>'http 200', 'data'=>$resp);
//        } else {
//            $response = array($code=>'0', 'rspmsg'=>'http response error curl_code:'.$curl_code.' curl_errno:'.$curl_errno.' curl_error:'.$curl_error.' resp:'.$resp);
//        }
//    }
//
//    return $response;
}

//拼接字符串
function makeQueryString($params)
{
    if (is_string($params))
        return $params;

    $query_string = array();
    foreach ($params as $key => $value)
    {
        array_push($query_string, rawurlencode($key) . '=' . rawurlencode($value));
    }
    $query_string = join('&', $query_string);
    return $query_string;
}

//拼接cookie字符串
function makeCookieString($params)
{
    if (is_string($params))
        return $params;

    $cookie_string = array();
    foreach ($params as $key => $value)
    {
        array_push($cookie_string, $key . '=' . $value);
    }
    $cookie_string = join('; ', $cookie_string);
    return $cookie_string;
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

function check_url($url){
    if(!preg_match("/^http[s]?.*/",$url)) return false;
    return true;
}

function getClientIp() {
    if(PHP_SAPI === 'cli') return '0.0.0.0';
    if(@$_REQUEST['_ipaddress']) return $_REQUEST['_ipaddress'];
    if(@$_SERVER['HTTP_X_FORWARDED_FOR']) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    if(@$_SERVER['REMOTE_ADDR']) return $_SERVER['REMOTE_ADDR'];
    return '';
}