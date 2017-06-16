<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

class ThirdController extends Controller {
    /**
     * @param sid   uc平台sid
     * @param game_id  uc平台游戏game_id
     * @return array
     */
    public function ucAction() {
        $sid = $this->parameter->tough('sid');
        $gameId = $this->parameter->tough('game_id');

        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_key'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $cfg =  array_merge($cfg, array(
            'baseUrl'=>'http://sdk.9game.cn',
            'port'=>'80',
            'prefix'=>'ng/cp/'
        ));

        $params = array(
            'sid' => $sid
        );
        //计算签名
        $sign = self::ucVerify($cfg, $params);

        ///////////////////组装请求参数-开始////////////////////
        $requestParam = array();
        $requestParam["id"] = substr(microtime(true) * 1000, 0, 13);//当前系统时间（毫秒）
        $requestParam["service"] = 'account.verifySession' ;//"account.verifySession";
        $requestParam["game"] = array('gameId'=>$gameId);
        $requestParam["client"] = 'language:php|version:1.3.0';
        $requestParam["data"] = $params;
        $requestParam["encrypt"] = "md5";
        $requestParam["sign"] = $sign;
        ///////////////////组装请求参数-结束/////////////////////

        //把参数序列化成一个json字符串
        $requestBody = json_encode($requestParam);
        //请求url
        $requestUrl = $cfg['baseUrl'] . ":" . $cfg['port'] . "/" . $cfg['prefix'] . "account.verifySession";

        //发送请求
        $res = http_curl($requestUrl, $requestBody);
        if(isset($res['state'])){
            if($res['state']['code'] == 1){
                return $res['data'];
            } else {
                throw new ApiException(ApiException::Remind, $res['state']['msg'].':'.$res['state']['code']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }

    protected static function ucVerify($cfg, $params, $notInKey = array()) {
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }
        return md5($enData.$cfg['app_key']);
    }


    /**
     * 获取联想平台用户id
     */
    public function lenovoAction() {
        $lpsust = $this->parameter->tough('lpsust');
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        $params = array(
            'lpsust'=>$lpsust,
            'realm'=>$cfg['app_id']
        );

        $url = 'http://passport.lenovo.com/interserver/authen/1.2/getaccountid';
        $res = http_curl($url, $params, false, array(), 'xml');
        return (array)$res;
    }

    /**
     * 获取百度平台用户id
     */
    public function baiduAction() {
        $token = $this->parameter->tough('token');

        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $appid = $cfg['app_id'];
        $appkey = $cfg['app_key'];

        $params = array(
            'AppID'=>$appid,
            'AccessToken'=>$token,
            'Sign'=>self::baiduVerify([$appid, $token, $appkey])
        );

        $url = 'http://querysdkapi.91.com/CpLoginStateQuery.ashx';
        $res = http_curl($url, $params, true);
        if(isset($res['Sign']) && $res['Sign']==self::baiduVerify([$appid, $res['ResultCode'], urldecode($res['Content']), $appkey])) {
            $result = base64_decode(urldecode($res['Content']));
            return json_decode($result,true);
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }

    /**
     * 计算签名
     * @param $params
     * @return string
     */
    protected function baiduVerify($params) {
        $v = array_values($params);
        return md5($v);
    }

    /**
     * 获取vivo平台用户id
     * @param authtoken
     */
    public function vivoAction() {
        $authtoken = $this->parameter->tough('authtoken');

        $url = 'https://usrsys.vivo.com.cn/sdk/user/auth.do';
        $res = http_curl($url, array('authtoken'=>$authtoken), true);
        if(isset($res['retcode'])) {
            if($res['retcode'] == 0){
                return $res['data'];
            } else {
                throw new ApiException(ApiException::Remind, trans('messages.error_third_system').$res['retcode']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }


}