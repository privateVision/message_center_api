<?php
namespace App\Http\Controllers\Web;
use App\Model\OrderExt;
use App\Model\Orders;
use App\Model\OrderExtend;

class MycardController extends \App\Controller {

    public function QueryAction() {
        $ordersExt = OrderExt::where('vcid', '-7')->get();
    }
}