<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ApiException;
use App\Exceptions\Exception;

class GooglePlayController extends Controller {

    /**
     * @param Request $request   token,out_trade_no,transaction_id,packageName,productId
     * @return array
     */
    protected function getData(Request $request) {
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

    protected function getOrderNo($data) {
        return $data['out_trade_no'];
    }

    protected function getTradeOrderNo($data, $order) {
        return $data['transaction_id'];
    }

    protected function verifySign($data, $order) {
        if(!isset($data['packageName']) || empty($data['packageName']) || !isset($data['productId']) || empty($data['productId']) || !isset($data['token']) || empty($data['token'])){
            return false;
        }
        return true;
    }

    protected function handler($data, $order) {
        return ($data['consumptionState'] == 1 && $data['purchaseState'] == 0) ? true: false;
    }

    protected function onComplete($data, $order, $isSuccess) {
        if($isSuccess){
            $code = ApiException::Success;
        } else {
            $code = ApiException::Error;
        }
        $content = [
            'code' =>$code,
            'msg' => null,
            'data' => null
        ];
        return response($content);
    }
}
