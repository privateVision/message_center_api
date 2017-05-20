<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;

use App\Model\Ucuser;
use App\Model\UcusersUUID;

class CommonController extends Controller {

    /**
     * @param sid   uc平台sid
     * @return array
     */
    public function getUcAccountAction() {
        $sid = $this->parameter->tough('sid');
        $gameId = $this->parameter->tough('gameId');
        $config = config('common.payconfig.uc');

        $params = array(
            'sid' => $sid
        );
        //计算签名
        $sign = self::ucVerify($config, $params);

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
        $requestUrl = $config['baseUrl'] . ":" . $config['port'] . "/" . $config['prefix'] . "account.getRealNameStatus";

        //发送请求
        $res = http_curl($requestUrl, $requestBody);
        if($res['cd'] == 1 && isset($res['state'])){
            if($res['state']['code'] == 1){
                return $res['data'];
            } else {
                throw new ApiException(ApiException::Remind, '错误码：'.$res['state']['code'] . $res['state']['msg']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }

    /**
     * @param $config
     * @param $params
     * @param array $notInKey
     * @return string
     */
    protected static function ucVerify($config, $params, $notInKey = array()) {
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }
        return md5($enData . $config['apikey']);
    }


}