<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\IosReceiptLog;

class IOSController extends Controller {

    use RequestAction;

    const PayMethod = '-6';
    const PayText = 'IOS';
    const PayTypeText = 'IOS';

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $appversion = $this->parameter->get('_app_version'); // 本来是必传参数，但兼容旧代码，所以无法必传

        $receipt = $this->parameter->tough('receipt');
        $transaction = $this->parameter->tough("transaction_id");
        $receipt_a = urldecode($receipt);
        $receipt_md5 = md5($receipt_a);

        $ios_receipt_log = IosReceiptLog::where('receipt_md5', $receipt_md5)->first();
        if($ios_receipt_log) {
            throw new ApiException(ApiException::Remind, trans("messages.apple_buy_success"));
        }

        $ios_receipt_log = new IosReceiptLog;
        $ios_receipt_log->receipt_md5 = $receipt_md5;
        $ios_receipt_log->receipt_base64 = $receipt;
        $ios_receipt_log->save();

        $isSandbox = $appversion ? $this->procedure_extend->isSandbox($appversion) : false;

        if ($isSandbox || env('APP_DEBUG')) {
            $verify_receipt_url = $config['verify_receipt_sandbox'];
        } else {
            $verify_receipt_url = $config['verify_receipt'];
        }

        $reqdata = json_encode(['receipt-data' => $receipt]);

        $ch = curl_init($verify_receipt_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqdata);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        log_debug('ios_verify_receipt', ['resdata' => $response], $verify_receipt_url);

        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("messages.ios_verify_fail"));
        }

        $response = json_decode($response, true);
        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("messages.ios_verify_fail"));
        }

        if (!isset($response['status']) || $response['status'] != 0) {
            throw new ApiException(ApiException::Remind, trans("messages.app_buy_faild") .' '. @$response['status']);
        }

        $success = false;

        /**
         [
            {
            "quantity":"1",
            "product_id":"com.anfeng.cqws600",
            "transaction_id":"1000000260288122",
            "original_transaction_id":"1000000260288122",
            "purchase_date":"2016-12-20 03:50:40 Etc/GMT",
            "purchase_date_ms":"1482205840000",
            "purchase_date_pst":"2016-12-19 19:50:40 America/Los_Angeles",
            "original_purchase_date":"2016-12-20 03:50:40 Etc/GMT",
            "original_purchase_date_ms":"1482205840000",
            "original_purchase_date_pst":"2016-12-19 19:50:40 America/Los_Angeles",
            "is_trial_period":"false"
            }
         ]
         */
        foreach($response['in_app'] as $v) {
            if($v['transaction_id'] == $transaction) {
                $success = true;

                $order_extend->third_order_no = $transaction;
                $order_extend->extra_params = ['verify_result' => $response];
                $order->asyncSave();

                order_success($order->id);
            }
        }

        return ['result' => $success];
    }
}