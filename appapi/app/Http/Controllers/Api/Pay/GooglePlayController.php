<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\ProceduresProducts;

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
        $packageName = $this->procedure_extend->package_name;
        $token = $this->parameter->tough('token');

        //获取订单扩展信息
        $product_id = isset($order_extend['product_id'])?$order_extend['product_id']:'';
        if(!$product_id){
            throw new ApiException(ApiException::Remind,  trans('order_extend_info_error'));
        }

        $product_extend = ProceduresProducts::find($product_id);
        if(!$product_extend){
            throw new ApiException(ApiException::Remind,  trans('order_extend_info_error'));
        }

        $status = self::handler($product_extend['third_product_id'], $packageName, $token);
        if($status == 1){
            //支付成功
            order_success($order->id);
        }

        return [
            'data' => array(
                'packageName'=>$packageName,
                'productId'=>$product_extend['third_product_id'],
                'token'=>$token,
                'status'=>$status
            )
        ];
    }

    protected function handler($productId, $packageName, $token){
         try {
            $config = config('common.payconfig.googleplay');
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$config['cert']);

            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

            //初始化服务
            $service = new \Google_Service_AndroidPublisher( $client );

            $optps = array();
            $resp = $service->purchases_products->get( $packageName, $productId, $token, $optps );
            log_info('googleplay_error', 'googleplay平台检查付款', json_encode($resp));
            if($resp['consumptionState'] == 1 && $resp['purchaseState'] == 0){
                return 1;
            } else {
                return 2;
            }
        } catch (\Exception $e) {
            log_error('googleplay_error', 'googleplay平台检查付款', $e->getCode().'|'.$e->getMessage());
            return 3;
        }
    }
}