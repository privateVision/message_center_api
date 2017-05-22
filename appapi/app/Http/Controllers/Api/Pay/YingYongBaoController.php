<?php

namespace App\Http\Controllers\Api\Pay;

use App\Model\Orders;
use Illuminate\Http\Request;
use App\Model\ProceduresExtend;
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

    const PayType = '-9';
    const PayText = 'yyb';
    const PayTypeText = '应用宝';

    //查询余额
    public function getBalanceMAction()
    {
        $params = array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'appid' => $this->parameter->tough('appid'),
            'ts' => time(),
            'pf' => $this->parameter->tough('pf'),
            'pfkey' => $this->parameter->tough('pfkey'),
            'zoneid' => $this->parameter->tough('zoneid'),
        );

        $accout_type = $this->parameter->tough('accout_type');

        return self::api_pay('/mpay/get_balance_m', $accout_type, $params, 'post', 'http');
    }

    //扣除游戏币
    public function payMAction()
    {
        $params = array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'appid' => $this->parameter->tough('appid'),
            'ts' => time(),
            'pf' => $this->parameter->tough('pf'),
            'pfkey' => $this->parameter->tough('pfkey'),
            'zoneid' => $this->parameter->tough('zoneid'),
            'amt'=>$this->parameter->tough('amt'),
            'billno'=>$this->parameter->tough('billno'),
        );

        $accout_type = $this->parameter->tough('accout_type');

        return self::api_pay('/mpay/pay_m', $accout_type, $params, 'post', 'http');
    }

    //取消支付
    public function cancelPayMAction()
    {
        $params = array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'appid' => $this->parameter->tough('appid'),
            'ts' => time(),
            'pf' => $this->parameter->tough('pf'),
            'pfkey' => $this->parameter->tough('pfkey'),
            'zoneid' => $this->parameter->tough('zoneid'),
            'amt'=>$this->parameter->tough('amt'),
            'billno'=>$this->parameter->tough('billno'),
        );

        $accout_type = $this->parameter->tough('accout_type');

        return self::api_pay('/mpay/cancel_pay_m', $accout_type, $params, 'post', 'http');
    }

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
        $secret = $this->procedure_extend->third_paykey.'&';

        $script_sig_name="/v3/r".$script_name;
        $sig = self::makeSig($method, $script_sig_name, $params, $secret);
        $params['sig'] = $sig;

        $url = $protocol . '://' . 'ysdk.qq.com' . $script_name;

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

}
