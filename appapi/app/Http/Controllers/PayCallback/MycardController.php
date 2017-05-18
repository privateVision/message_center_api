<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class MycardController extends Controller
{
    protected function getData(Request $request) {
        return $_POST;
    }

    protected function getOrderNo($data) {
        return $data['FacTradeSeq'];
    }

    protected function getTradeOrderNo($data, $order) {
        return $data['MyCardTradeNo'];
    }

    protected function verifySign($data, $order) {
        $config = config('common.payconfig.mycard');
        return static::mycard_hash($data, $config['FacServerKey']) === $data['Hash'];
    }

    protected function handler($data, $order) {
        // TODO 验证交易结果
        return @$data['PayResult'] == '3';
    }

    protected function onComplete($data, $order, $isSuccess) {
        $html = '';
        $html.= '<doctype html!>';
        $html.= '<html>';
        $html.= '<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">';
        $html.= '<head>';
        $html.= '<title></title>';
        $html.= '<script type="text/javascript">';

        if($isSuccess) {
            $msg = trans('messages.mycard_callback_success', ['name' => $order ? $order->subject : trans('messages.product_default_name')]);
            $html .= 'window.mycard.payResult(true, "'.$msg.'")';
        } else {
            $msg = trans('messages.mycard_callback_fail', ['name' => $order ? $order->subject : trans('messages.product_default_name'), 'ReturnMsg' => urldecode(@$data['ReturnMsg'])]);
            $html .= 'window.mycard.payResult(false, "'.$msg.'")';
        }

        $html.= '</script>';
        $html.= '</head>';
        $html.= '<body>';
        $html.= $msg;
        $html.= '<body>';
        $html.= '</html>';

        return $html;
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