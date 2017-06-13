<?php // 开源DEMO https://github.com/paypal/ipn-code-samples/blob/master/php/PaypalIPN.php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;

class PaypalController extends Controller
{

    protected function getData(Request $request) {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                if ($keyval[0] === 'payment_date') {
                    if (substr_count($keyval[1], '+') === 1) {
                        $keyval[1] = str_replace('+', '%2B', $keyval[1]);
                    }
                }
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        return $myPost;
    }

    protected function getOrderNo($data) {
        return $data['invoice'];
    }

    protected function getTradeOrderNo($data, $order, $order_extend) {
        return $data['txn_id'];
    }

    protected function verifySign($data, $order, $order_extend) {
        $config = configex('common.payconfig.paypal');
        return $config['business'] === $data['business'];
    }

    protected function handler($data, $order, $order_extend) {
        $req = 'cmd=_notify-validate';
        $get_magic_quotes_exists = false;
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($data as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        $url = env('APP_DEBUG', true) ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // This is often required if the server is missing a global cert bundle, or is using an outdated one.
        //if ($this->use_local_certs) {
        //    curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cert/cacert.pem");
        //}
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        $res = curl_exec($ch);

        log_info('paypal_verify', ['reqdata' => $req, 'resdata' => $res], $url);

        if (!$res) {
            curl_close($ch);
            return false;
        }

        $info = curl_getinfo($ch);
        curl_close($ch);
        $http_code = $info['http_code'];
        if ($http_code != 200) {
            return false;
        }

        if ($res == 'VERIFIED') {
            return true;
        } else {
            return false;
        }
    }

    protected function onComplete($data, $order, $order_extend, $isSuccess, $message = null) {
        return $isSuccess ? 'success' : 'fail';
    }
}