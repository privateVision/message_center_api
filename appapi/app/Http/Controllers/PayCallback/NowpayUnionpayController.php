<?php
namespace App\Controller\External;

use App\Exceptions\PayCallbackException;
use Illuminate\Http\Request;
use App\Model\Orders;

class NowpayUnionpayController extends \App\Controller
{

    protected function getData(Request $request) {

    }

    protected function getOrderNo($data) {

    }

    protected function getOuterOrderNo($data, Orders $order) {

    }

    protected function verifySign($data, Orders $order) {

    }

    protected function handler($data, Orders  $order){

    }

    protected function onComplete($data, Orders $order, $isSuccess, $code) {
        
    }
}