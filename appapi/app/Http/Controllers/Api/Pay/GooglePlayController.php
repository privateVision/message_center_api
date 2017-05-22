<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;

class GooglePlayController extends Controller {

    use RequestAction;

    const PayMethod = '-8';
    const PayText = 'googleplay';
    const PayTypeText = 'GooglePlay平台支付';

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
     * @param order_id
     * @param token
     */
    public function payStatusAction(Request $request){
        $order_id = $this->parameter->tough('order_id');
        $token = $this->parameter->tough('token');

        $order = null;
        if($order_id) {
            $order = Orders::from_cache_sn($order_id);
        }

        if(!$order) {
            throw new ApiException(ApiException::Remind,  trans('order_not_exist'));
        }

        //获取订单扩展信息



        try {
            $config = config('common.payconfig.googleplay');
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$config['cert']);

            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

            //初始化服务
            $service = new \Google_Service_AndroidPublisher( $client );

            $packageName = $_REQUEST['packageName'];
            $productId = $_REQUEST['productId'];
            $token = $_REQUEST['token'];
            $optps = array();
            $resp = $service->purchases_products->get( $packageName, $productId, $token, $optps );

            return array_merge($_REQUEST, $resp);

        } catch (\Exception $e) {
            log_error('googleplay_error', null, $e->getCode().'|'.$e->getMessage());
            return $_REQUEST;
        }
    }
}