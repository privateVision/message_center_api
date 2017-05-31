<?php
namespace App\Http\Controllers\Api\Pay ;

use App\Exceptions\ApiException;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\IosReceiptLog;

class  AppleController extends Controller {

    use RequestAction;

    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
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

        $is_sandbox = $this->procedure_extend->enable & (1 << 7) != 0;

        if ($is_sandbox || env('APP_DEBUG')) {
            $verify_receipt_url = $config['verify_receipt_sandbox'];
        } else {
            $verify_receipt_url = $config['verify_receipt'];
        }

        $reqdata = ['receipt-data' => $receipt];

        $ch = curl_init($verify_receipt_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqdata);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        log_debug('ios_verify_receipt', ['resdata' => $response, 'reqdata' => $reqdata], $verify_receipt_url);

        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("messages.ios_verify_fail"));
        }

        $response = json_decode($response, true);
        if(!$response) {
            throw new ApiException(ApiException::Remind, trans("messages.ios_verify_fail"));
        }

        if (!isset($response['status']) || $response['status'] != 0) {
            throw new ApiException(ApiException::Remind, trans("messages.app_buy_faild"));
        }

        return [];
    }
}