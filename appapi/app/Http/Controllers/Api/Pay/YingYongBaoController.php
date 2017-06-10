<?php

namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;
/**
 * 错误码定义
 */
define('OPENAPI_ERROR_REQUIRED_PARAMETER_EMPTY', 1801); // 参数为空
define('OPENAPI_ERROR_REQUIRED_PARAMETER_INVALID', 1802); // 参数格式错误
define('OPENAPI_ERROR_RESPONSE_DATA_INVALID', 1803); // 返回包格式错误
define('OPENAPI_ERROR_CURL', 1900); // 网络错误, 偏移量1900, 详见 http://curl.haxx.se/libcurl/c/libcurl-errors.html

class YingYongBaoController extends Controller
{
    use RequestAction;

    const PayMethod = '-9';
    const PayText = 'yingyongbao';
    const PayTypeText = '应用宝平台支付';

    /**
     * yingyongbao config
     * {
     *      "qq_app_id":"f28613464145a3a861869bc4ae35335f",
     *      "qq_app_key":"81tKZhcpxI0wOoGgSwcgwk0WC",
     *      "wx_app_id":"f28613464145a3a861869bc4ae35335f",
     *      "wx_app_key":"81tKZhcpxI0wOoGgSwcgwk0WC"
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param OrderExtend $order_extend
     * @param $real_fee
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee)
    {
        if($this->payM($order, $order_extend)){
            order_success($order->id);
            return [
                'result'=>true
            ];
        }
    }

    /**
     * 初始化数据
     * @param openid
     * @param openkey
     * @param paytoken
     * @param pf
     * @param pfkey
     * @return array
     */
    protected function getParams()
    {
        return array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'pay_token' => $this->parameter->tough('paytoken'),
            'pf' => $this->parameter->tough('pf'),
            'pfkey' => $this->parameter->tough('pfkey'),
            'zoneid' => 1,
            'ts' => time()
        );
    }

    /**
     * 检查支付token是否失效
     * @param accout_type   qq或者wx
     * @param openid
     * @param openkey
     * @return array
     */
    public function checkPayTokenAction()
    {
        $accout_type = $this->parameter->tough('accout_type');
        $appids = explode(',', $this->procedure_extend->third_appid);
        $appkeys = explode(',', $this->procedure_extend->third_appkey);
        $params = array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'timestamp' => time(),
            'appid'=>($accout_type=='qq'?$appids[0]:$appids[1])
        );
        $params['sig'] =  md5(($accout_type=='qq'?$appkeys[0]:$appkeys[1]).$params['timestamp']);

        $method = 'get';
        $script_name = $accout_type=='qq'?'/auth/qq_check_token':'/auth/wx_check_token';

        $res = self::api_ysdk($script_name, $params, $method);
        if($res['ret'] === 0) {
            return ['result'=>'success'];
        } else {
            throw new ApiException(ApiException::Remind, $res['msg']);
        }
    }

    /**
     * 获取用户余额
     * @param accout_type qq或者wx
     * @return array
     */
    public function getBalanceMAction()
    {
        $accout_type = $this->parameter->tough('accout_type');

        $params = self::getParams();

        $res = self::api_pay('/mpay/get_balance_m', $accout_type, $params);

        if($res['ret'] === 0) {
            return $res;
        } else {
            throw new ApiException(ApiException::Remind, $res['msg']);
        }
    }

    /**
     * 扣除游戏币
     * @param order
     * @param order_extend
     * @param accout_type qq或者wx
     * @return bool
     */
    public function payM($order, $order_extend)
    {
        $accout_type = $this->parameter->tough('accout_type');

        $params = self::getParams();
        $params['amt'] = $this->parameter->tough('amt');
        $params['billno'] = $order->sn;
        //检查分区
        if(!empty($order_extend->zone_id) && $order_extend->zone_id>1) {
            $params['zoneid'] = $order_extend->zone_id;
        }

        $res = self::api_pay('/mpay/pay_m', $accout_type, $params);

        if(isset($res['ret']) && $res['ret'] === 0){
            return true;
        }else{
            throw new ApiException(ApiException::Remind, $res['msg']);
        }
    }

    /**
     * 执行API调用，返回结果数组
     *
     * @param string $script_name 调用的API方法，比如/auth/verify_login，
     *                             参考 http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.1 Oauth服务
     * @param array  $params 调用API时带的参数
     * @param string $method 请求方法 post
     * @param string $protocol 协议类型 http / https
     * @return array 结果数组
     */
    public function api_ysdk($script_name, $params,  $method='post', $protocol='http')
    {

        // add some params: 'version'
        //$params['version'] = 'PHP YSDK v1.0.0';

        $url = $protocol . '://'. self::getDomain() . $script_name;

        // 通过调用以下方法，可以打印出最终发送到YSDK API服务器的请求参数以及url，默认为注释
//        self::printRequest($url,$params,$method);

        $cookie = array();

        // 发起请求
        $ret = self::makeRequest($url, $params, $cookie, $method, $protocol);

        if (false === $ret['result'])
        {
            $result_array = array(
                'ret' => OPENAPI_ERROR_CURL + $ret['errno'],
                'msg' => $ret['msg'],
            );
        }
        else
        {
            $result_array = json_decode($ret['msg'], true);

            // 远程返回的不是 json 格式, 说明返回包有问题
            if (is_null($result_array)) {
                $result_array = array(
                    'ret' => OPENAPI_ERROR_RESPONSE_DATA_INVALID,
                    'msg' => $ret['msg']
                );
            }
        }

        // 通过调用以下方法，可以打印出调用openapi请求的返回码以及错误信息，默认注释
//        self::printRespond($result_array);

        return $result_array;
    }

    /**
     * 执行API 支付调用，返回结果数组
     *
     * @param string $script_name 调用的API方法，比如/auth/verify_login，
     *                             参考 http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.1 Oauth服务
     * @param array  $params 调用API时带的参数
     * @param string $method 请求方法 post
     * @param string $protocol 协议类型 http / https
     * @return array 结果数组
     */
    public function api_pay($script_name,$accout_type,$params,$method='post', $protocol='http')
    {

        // 添加一些参数
        $params['appid'] = $this->procedure_extend->third_payid;
        $params['format'] = 'json';

        $cookie=array();
        $cookie["org_loc"] = urlencode($script_name);
        if( $accout_type == "qq")
        {
            $cookie["session_id"] = "openid";
            $cookie["session_type"] = "kp_actoken";
        }
        else if( $accout_type == "wx" )
        {
            $cookie["session_id"] = "hy_gameid";
            $cookie["session_type"] = "wc_actoken";
        }
        else
        {
            return OPENAPI_ERROR_REQUIRED_PARAMETER_INVALID;
        }

        // 无需传sig, 会自动生成
        unset($params['sig']);

        // 生成签名
        $secret = $this->procedure_extend->third_paykey;

        $script_sig_name="/v3/r".$script_name;
        $sig = self::makeSig($method, $script_sig_name, $params, $secret);
        $params['sig'] = $sig;

        $url = $protocol . '://' . self::getDomain() . $script_name;

        // 通过调用以下方法，可以打印出最终发送到openapi服务器的请求参数以及url，默认为注释
//        self::printCookies($cookie);
//        self::printRequest($url,$params,$method);

        // 发起请求
        $ret = self::makeRequest($url, $params, $cookie, $method, $protocol);

        if (false === $ret['result'])
        {
            $result_array = array(
                'ret' => OPENAPI_ERROR_CURL + $ret['errno'],
                'msg' => $ret['msg'],
            );
        }

        $result_array = json_decode($ret['msg'], true);

        // 远程返回的不是 json 格式, 说明返回包有问题
        if (is_null($result_array)) {
            $result_array = array(
                'ret' => OPENAPI_ERROR_RESPONSE_DATA_INVALID,
                'msg' => $ret['msg']
            );
        }

        // 通过调用以下方法，可以打印出调用支付API请求的返回码以及错误信息，默认注释
//        self::printRespond($result_array);

        return $result_array;
    }

    static public function getDomain() {
        return env('APP_DEBUG')?'ysdktest.qq.com':'ysdk.qq.com';
    }

    /**
     * @param $method
     * @param $url_path
     * @param $params
     * @param $appkey
     * @return string
     */
    static public function makeSig($method, $url_path, $params, $appkey)
    {
        $secret = $appkey.'&';

        $mk = self::makeSource($method, $url_path, $params);
        $my_sign = hash_hmac("sha1", $mk, strtr($secret, '-_', '+/'), true);
        $my_sign = base64_encode($my_sign);

        return $my_sign;
    }

    static private function makeSource($method, $url_path, $params)
    {
        $strs = strtoupper($method) . '&' . rawurlencode($url_path) . '&';

        ksort($params);
        $query_string = array();
        foreach ($params as $key => $val )
        {
            array_push($query_string, $key . '=' . $val);
        }
        $query_string = join('&', $query_string);

        return $strs . str_replace('~', '%7E', rawurlencode($query_string));
    }

    /**
     * 执行一个 HTTP 请求
     *
     * @param string 	$url 	执行请求的URL
     * @param mixed	$params 表单参数
     * 							可以是array, 也可以是经过url编码之后的string
     * @param mixed	$cookie cookie参数
     * 							可以是array, 也可以是经过拼接的string
     * @param string	$method 请求方法 post / get
     * @param string	$protocol http协议类型 http / https
     * @return array 结果数组
     */
    static public function makeRequest($url, $params, $cookie, $method='post', $protocol='http')
    {
        $query_string = self::makeQueryString($params);
        $cookie_string = self::makeCookieString($cookie);

        $ch = curl_init();

        if ('get' == $method)
        {
            curl_setopt($ch, CURLOPT_URL, "$url?$query_string");
        }
        else
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        // disable 100-continue
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        if (!empty($cookie_string))
        {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
        }

        if ('https' == $protocol)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (false === $ret || !empty($err))
        {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            return array(
                'result' => false,
                'errno' => $errno,
                'msg' => $err,
                'info' => $info,
            );
        }

        curl_close($ch);

        return array(
            'result' => true,
            'msg' => $ret,
        );

    }

    static public function makeQueryString($params)
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

    static public function makeCookieString($params)
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

    /**
     * 打印出请求串的内容，当API中的这个函数的注释放开将会被调用。
     *
     * @param string $url 请求串内容
     * @param array $params 请求串的参数，必须是array
     * @param string $method 请求的方法 get / post
     */
    protected function printRequest($url, $params, $method)
    {
        $query_string = self::makeQueryString($params);
        if($method == 'get')
        {
            $url = $url."?".$query_string;
        }
        echo "\n============= request info ================\n\n";
        print_r("method : ".$method."\n");
        print_r("url    : ".$url."\n");

        if($method == 'post')
        {
            print_r("query_string : ".$query_string."\n");
        }
        echo "\n";
        print_r("params : ".print_r($params, true)."\n");
        echo "\n";

    }

    /**
     * 打印出请求的cookies，当API中的这个函数的注释放开将会被调用。
     *
     * @param array $cookies 待打印的cookies
     */
    protected function printCookies($cookies)
    {
        echo "\n============= cookie info ================\n\n";
        print_r("cookies : ".print_r($cookies, true)."\n");
        echo "\n";

    }

    /**
     * 打印出返回结果的内容，当API中的这个函数的注释放开将会被调用。
     *
     * @param array $array 待打印的array
     */
    protected function printRespond($array)
    {
        echo "\n============= respond info ================\n\n";
        print_r($array);
        echo "\n";
    }

}
