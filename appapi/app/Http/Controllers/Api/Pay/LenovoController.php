<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class LenovoController extends Controller {

    use RequestAction;

    const PayMethod = '-14';
    const PayText = 'lenovo';
    const PayTypeText = '联想平台支付';

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        return [
            'data' => array()
        ];
    }

    /**
     * 获取平台用户id
     */
    public function getAccoutAction() {
        $lpsust = $this->parameter->tough('lpsust');

        $params = array(
            'lpsust'=>$lpsust,
            'AccessToken'=>$this->procedure_extend->package_name
        );

        $url = 'http://passport.lenovo.com/interserver/authen/1.2/getaccountid';
        $res = http_curl($url, $params, false);
        if($res['cd'] == 1) {
            return json_decode(json_encode(simplexml_load_string($res['data'])),TRUE);
        } else {
            throw new ApiException(ApiException::Remind, $res['rspmsg']);
        }
    }

}