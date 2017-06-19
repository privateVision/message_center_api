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
     *      "qq_app_id":"1105442440",
     *      "qq_app_key":"zyWX2zZZ7T8ZZorE",
     *      "wx_app_id":"wxf671fb41a6dbcc03",
     *      "wx_app_key":"71e9270844dca0cad18e25ebdca4f7b6",
     *      "pay_id":"1105442440",
     *      "pay_key":"gdcaJrEZwdmDDoaYw4rAwvdz8uIJXYkH" //支付测试key
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
        if($this->payM($order, $order_extend, $real_fee)){
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
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['qq_app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $accout_type = $this->parameter->tough('accout_type');
        $app_id = $accout_type=='qq'?$cfg['qq_app_id']:$cfg['wx_app_id'];
        $app_key = $accout_type=='qq'?$cfg['qq_app_key']:$cfg['wx_app_key'];

        $params = array(
            'openid' => $this->parameter->tough('openid'),
            'openkey' => $this->parameter->tough('openkey'),
            'timestamp' => time(),
            'appid'=>$app_id
        );
        $params['sig'] =  md5($app_key.$params['timestamp']);

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
    public function payM($order, $order_extend, $real_fee)
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
            return $res;
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

        $cookie = array();

        // 发起请求
        $is_post = $method=='post'?true: false;
        $result_array = self::makeRequest($url, $params, $is_post);

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
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['pay_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        // 添加一些参数
        $params['appid'] = $cfg['pay_id'];
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
        $secret = $cfg['pay_key'];

        $script_sig_name="/v3/r".$script_name;
        $sig = self::makeSig($method, $script_sig_name, $params, $secret);
        $params['sig'] = $sig;

        $url = $protocol . '://' . self::getDomain() . $script_name;

        // 发起请求
        $is_post = $method=='post'?true: false;
        $result_array = http_curl($url, $params, $is_post, array(
            CURLOPT_COOKIE=>$cookie
        ));

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

}
