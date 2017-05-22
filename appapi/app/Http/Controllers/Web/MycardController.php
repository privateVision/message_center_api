<?php
namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;
use App\Model\OrderExt;
use App\Model\Orders;
use App\Model\OrderExtend;

class MycardController extends \App\Controller {

    public function QueryAction() {
        $ordersExt = OrderExtend::where('vcid', '-7')->get();
    }

    public function RescueAction(Request $request) {
        $config = config('common.payconfig.mycard');
        if($request->ip() !== $config['server_host']) {
            return '';
        }
    }
}