<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class LenovoController extends Controller {

    use RequestAction;

    const PayMethod = '-14';
    const PayText = 'lenovo';
    const PayTypeText = '联想平台支付';

    /**
     * lenovo config
     * {
     *      "app_id":"1702150608789.app.ln",
     *      "app_key":"MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBALKN2hE/ke/IP4KptsroyNMeraqBg6lhs3IUflhfXb3mPPGd7R8VCjYsBwzw4MvsdnQYzxn3LYus5kg2J8u0y7+bk41oobhKM/7hPqRmq0p2DYw7lvfhhRTp+5icjn0JRTmAx4xiZ80+Px8AwZ7hQFZUHJf0Ogbx9ZgTrg0C43zVAgMBAAECgYALPQx1u3eXDRaaRc5glShWyX6K1d4QojqmOo39R/thgYVie9s58pwS7tB+ywaLL1YBVrJqYvl16isQboAwvS95w/d+8UEZ78P1mVTAcVp4xNRGvMS4C+wNVLJYa/0GNAjDvxp6qr6ZTKO5Ta5Dp1lIXB7oqLVQ5D5sbZUqxPBNfQJBAOYXZ5WZhl5NfgkP9WL2LDFEtQyhbS/d5k4pdZ/ckvxtjQ323ioFMXIIuEl/DklqBU4ZBoDa4mwUN33CAQaFbT8CQQDGqNZHNO3JhnkRoPOF2phiogR8IlxrRSw0MyFKbeJJ+WLaUXh2Pj0EMJIx+wzcyltc0e7wDY7MTLNP4XmEJszrAkAXGuCO+DSzAYsXc9/LSTcU13Zqx0cEmH7I+IbUP70O1h1k+pZCl/ToI5IF51lS6++OcRrjE5fLDJip6zJZKkrXAkBSf6r8xy44km+UspJu8+h0jXPvWRWoNoG068bXceqXbclvgIXWFOKh6snLl8YvqplmYogniHnUvcV5Vtlv1+0hAkBo5DWxKlhnR3ijTL77vCACGOSsXnV9LueuEKYrVSmjJ1CQ628EHXJW4BN90KbQw+NhkROKOWbSGtcpoEkuz9I6"
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {
        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        $params = array(
            'appid'=>$cfg['app_id'],
            'waresid'=>$this->parameter->tough('waresid'),
            'price'=>$real_fee,
            'exorderno'=>$order->sn,
            'notifyurl'=>url('pay_callback/lenovo'),
            'cpprivateinfo'=>'123456'
        );

        return [
            'data' => $params
        ];
    }

}