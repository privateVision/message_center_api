<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class AmigoController extends Controller {

    use RequestAction;

    const PayMethod = '-15';
    const PayText = 'amigo';
    const PayTypeText = '金立平台支付';
    const PayHttp = 'https://id.gionee.com';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {

        $params = array(
            'api_key'=>$this->procedure_extend->third_appkey,
            'deal_price'=>$real_fee,
            'deliver_type'=>1,
            'out_order_no'=>$order->sn,
            'subject'=>$order->subject,
            'submit_time'=>date('Y-m-d H:i:s'),
            'total_fee'=>$real_fee
        );

        return [
            'data' => $params
        ];
    }


    /**
     * 获取金立平台用户id
     */
    public function getAccoutAction() {
        $token = $this->parameter->tough('token');

        $appkey = $this->procedure_extend->third_appkey;
        $appsecret = $this->procedure_extend->third_appsecret;

        $params = array(
            'ts'=>time(),
            'nonce'=>strtoupper(substr(uniqid(),0,8)),
            'method'=>'POST',
            'uri'=>'/account/verify.do',
            'host'=>'id.gionee.com',
            'port'=>443
        );
        $sign = self::verify($params, $appsecret);
        $Authorization = 'MAC id="'.$appkey.'",ts="'.$params['ts'].'",nonce="'.$params['nonce'].'",mac="'.$sign.'"';

        $url = self::PayHttp . $params['url'];
        $herder = array('Authorization: '.$Authorization);
        $res = http_curl($url, $token, true, 'cd', $herder);
        if($res['cd'] == 1 && isset($res['r'])) {
            return $res;
        } else {
            throw new ApiException(ApiException::Remind, $res['rspmsg']);
        }
    }

    /**
     * 计算签名
     * @param $params
     * @return string
     */
    protected function verify($params, $secretKey) {
        $vals = array_values($params);
        $str = implode("\n", $vals);
        $signature_str = $str . "\n"."\n";
        return base64_encode(hash_hmac('sha1', $signature_str, $secretKey, true));
    }

    /**
     * 创建订单的签名格式
     */
    protected function getSign($params, $prisecret) {
        ksort($params);
        $vals = array_values($params);
        $str = implode('', $vals);

    }
}