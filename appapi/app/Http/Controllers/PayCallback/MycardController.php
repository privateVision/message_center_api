<?php
namespace App\Http\Controllers\PayCallback;

use App\Exceptions\Exception;
use Illuminate\Http\Request;

class MycardController extends Controller
{

    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['FacTradeSeq'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend) {
        return $data['MyCardTradeNo'];
    }

    protected function verifySign($data, $order, $order_extend) {
        $config = config('common.payconfig.mycard');
        return static::mycard_hash($data, $config['FacServerKey']) === $data['Hash'];
    }

    protected function handler($data, $order, $order_extend) {
        $config = config('common.payconfig.mycard');

        // 验证交易结果
        $resdata = ['AuthCode' => $order_extend->extra_params['AuthCode']];
        $result = http_request($config['TradeQuery'], $resdata, true);
        log_debug('mycardTradeQuery', ['result' => $result], $config['TradeQuery']);

        if(!$result) {
            throw new Exception(trans('messages.order_handle_fail'), 0);
        }

        $result = json_decode($result, true);
        if(!$result) {
            throw new Exception(trans('messages.order_handle_fail'), 0);
        }

        if(@$result['ReturnCode'] != 1 && @$result['PayResult'] != 3) {
            throw new Exception(@$result['ReturnMsg'] ? urldecode($result['ReturnMsg']) : trans('messages.order_handle_fail'), 0);
        }

        // 开始请款交易
        $result = http_request($config['PaymentConfirm'], $resdata, true);
        log_debug('mycardPaymentConfirm', ['result' => $result], $config['PaymentConfirm']);

        if(!$result) {
            throw new Exception(trans('messages.order_handle_fail'), 0);
        }

        $result = json_decode($result, true);
        if(!$result) {
            throw new Exception(trans('messages.order_handle_fail'), 0);
        }

        if(@$result['ReturnCode'] != 1) {
            throw new Exception(@$result['ReturnMsg'] ? urldecode($result['ReturnMsg']) : trans('messages.order_handle_fail'), 0);
        }

        $order_extend->extra_params = ['TradeSeq' => $result['TradeSeq']];

        return @$data['PayResult'] == '3';
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess) {
        if($isSuccess) {
            return trans('messages.mycard_callback_success', [
                'name' => $order ? $order->subject : trans('messages.product_default_name')
            ]);
        } else {
            return trans('messages.mycard_callback_fail', [
                'name' => $order ? $order->subject : trans('messages.product_default_name'),
                'ReturnMsg' => urldecode(@$data['ReturnMsg'])
            ]);
        }
    }

    protected static function mycard_hash($data, $key) {
        $str = implode('', [
            $data['ReturnCode'],
            $data['PayResult'],
            $data['FacTradeSeq'],
            $data['PaymentType'],
            $data['Amount'],
            $data['Currency'],
            $data['MyCardTradeNo'],
            $data['MyCardType'],
            $data['PromoCode'],
            $key
        ]);

        $str = urlencode($str);

        $str = preg_replace_callback('/%[0-9A-F]{2}/', function($matches) {
            return strtolower($matches[0]);
        }, $str);

        $sign = hash('sha256', $str, true);

        return bin2hex($sign);
    }
}