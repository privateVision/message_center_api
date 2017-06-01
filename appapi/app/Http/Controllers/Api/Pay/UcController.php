<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;

class UcController extends Controller {

    use RequestAction;

    const PayMethod = '-11';
    const PayText = 'uc';
    const PayTypeText = 'uc平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        //获取uc用户id
        $account = self::getUcAccount($config);

        $params['accountId'] = $account['accountId'];
        $params['amount'] = $real_fee;
        $params['notifyUrl'] = urlencode(url('pay_callback/uc'));
        $params['cpOrderId'] = $this->parameter->tough('order_id');
        $params['callbackInfo'] = '';

        $notInKey =  array("roleName", "roleId", "grade", "serverId", "signType");
        $params['sign'] = self::verify($config, $params, $notInKey);

        return [
            'data' => $params
        ];
    }

    /**
     * @param sid   uc平台sid
     * @param game_id  uc平台游戏game_id
     * @return array
     */
    public function getUcAccout($config = array()) {
        $sid = $this->parameter->tough('sid');
        $gameId = $this->parameter->tough('game_id');
        if(empty($config)) {
            $config = config('common.payconfig.uc');
        }

        $params = array(
            'sid' => $sid
        );
        //计算签名
        $sign = self::verify($config, $params);

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
                throw new ApiException(ApiException::Remind, $res['state']['msg'].':'.$res['state']['code']);
            }
        } else {
            throw new ApiException(ApiException::Remind, trans('messages.http_request_error'));
        }
    }

    protected static function verify($config, $params, $notInKey = array()) {
        ksort($params);
        $enData = '';
        foreach( $params as $key=>$val ){
            if(in_array($key, $notInKey)){
                continue;
            }
            $enData = $enData.$key.'='.$val;
        }
        return md5($enData.$config['apikey']);
    }


}