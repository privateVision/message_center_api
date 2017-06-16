<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class PaypalECController extends Controller
{

    protected function getData(Request $request) {
        $sign = $request->get('sign');

        $data = decrypt3des($sign);
        if(!$data) return [];

        $data = json_decode($data, true);
        if(!$data) return [];

        $data['PayerID'] = $request->get('PayerID');
        $data['paymentId'] = $request->get('paymentId');
        return $data;
    }

    protected function getOrderNo($data) {
        return $data['order_no'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend) {
        return $data['paymentId'];
    }

    protected function verifySign($data, $order, $order_extend) {
        return $order_extend->extra_params['payment']['id'] === $data['paymentId'];
    }

    protected function handler($data, $order, $order_extend) {
        if($data['type'] == 'return') {
            $access_token = $data['access_token'];
            if (!$access_token) return false;

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token,
            ];

//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $order_extend->extra_params['payment']['links']['execute']['href']);
//            curl_setopt($ch, CURLOPT_POST, true);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"payer_id\": \"{$data['PayerID']}\"}");
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            $response = curl_exec($ch);
//            curl_close($ch);
//
//            log_info('paypal-ec-execute', ['reqdata' => 'grant_type=client_credentials', 'resdata' => $response], $order_extend->extra_params['payment']['links']['execute']['href']);

            $url = $order_extend->extra_params['payment']['links']['execute']['href'];
            $params = "{\"payer_id\": \"{$data['PayerID']}\"}";
            $response = http_curl($url, $params, true, array(
                CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_CONNECTTIMEOUT=>60
            ), 'str');

            if(!$response) {
                return false;
            }

            $response = json_decode($response, true);
            if(!$response) {
                return false;
            }

            $order_extend->extra_params = ['execute' => $response];

            return @$response['id'] == $data['paymentId'] && @$response['payer']['status'] == 'VERIFIED';
        }

        return true;
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null) {
        if($data['type'] == 'return') {
            return view('pay_callback/callback', ['order' => $order->id, 'is_success' => true]);
        } else {
            return view('pay_callback/callback', ['order' => $order->id, 'is_success' => false, 'message' => trans('messages.pay_cancel')]);
        }
    }
}