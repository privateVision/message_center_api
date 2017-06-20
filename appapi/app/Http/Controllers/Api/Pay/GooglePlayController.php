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
     * googleplay config
     * 比较特殊 不用填写所有参数
     */

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $package_name = $this->procedure_extend->package_name;
        $token = $this->parameter->tough('token');

        //获取订单扩展信息
        $product_id = $order_extend->product_id;
        if(!$product_id){
            throw new ApiException(ApiException::Remind,  trans('messages.order_extend_info_error'));
        }

        $product_extend = ProceduresProducts::find($product_id);
        if(!$product_extend){
            throw new ApiException(ApiException::Remind,  trans('messages.order_extend_info_error'));
        }

        //获取google_play配置文件
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['project_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $file = resource_path('google_play') . DIRECTORY_SEPARATOR . $this->procedure_extend->pid . '_' . md5(json_encode($cfg)) . '.json';
        if(!file_exists($file)) {
            file_put_contents($file, json_encode($cfg));
        }
        $config['cert'] = $file;

        //验证付款状态
        self::handler($config, $product_extend->third_product_id, $package_name, $token);

        //支付成功
        order_success($order->id);

        return [
            'data' => array(
                'package_name'=>$package_name,
                'third_product_id'=>$product_extend->third_product_id,
                'token'=>$token
            )
        ];
    }

    public function checkPay() {
        $token = $this->parameter->tough('token');
        $third_product_id = $this->parameter->tough('third_product_id');
        $package_name = $this->procedure_extend->package_name;

        //获取google_play配置文件
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['project_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }
        $file = resource_path('google_play') . DIRECTORY_SEPARATOR . $this->procedure_extend->pid . '_' . md5(json_encode($cfg)) . '.json';
        if(!file_exists($file)) {
            file_put_contents($file, json_encode($cfg));
        }
        $config['cert'] = $file;

        //验证付款状态
        self::handler($config, $third_product_id, $package_name, $token);

        return [
            'token'=>$token,
            'package_name'=>$package_name,
            'third_product_id'=>$third_product_id
        ];
    }

    protected function handler($config, $productId, $packageName, $token){
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$config['cert']);

        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

        //初始化服务
        $service = new \Google_Service_AndroidPublisher( $client );

        $optps = array();
        $resp = $service->purchases_products->get( $packageName, $productId, $token, $optps );
        log_info('googleplay', ['resdata'=>$resp], 'googleplay平台检查付款');

        if(isset($resp['purchaseState']) && $resp['purchaseState'] === 0) {
            return true;
        } else {
            throw new ApiException(ApiException::Remind,  trans('messages.error_googlepaly_verify'));
        }
    }
}