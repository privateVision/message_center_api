<?php
namespace App\Controller\External;

use Illuminate\Http\Request;
use App\Model\Orders;

abstract class Controller extends \App\Controller
{
    
    public function CallbackAction(Request $request) {
        try {
            $data = $this->getData($request);

            log_info('callback_request', ['route' => $request->path(), 'data' => $data]);

            $sn = $this->getOrderNo($data); 
            $order = null;
        
            if($sn) {
                $order = Orders::from_cache_sn($sn);
            }

            if(!$order) {
                log_error('callback_order_not_exists', $data);
                return $this->onComplete($data, null, true);
            }
            
            if($order->status != Orders::Status_WaitPay) {
                log_error('callback_order_status_error', ['sn' => $sn]);
                return $this->onComplete($data, null, true);
            }
            
            if(!$this->verifySign($data, $order)) {
                log_error('callback_order_verify_sign_fail', ['route' => $request->path(), 'data' => $data]);
                return $this->onComplete($data, null, false);
            }

            $outer_order_no = $this->getTradeOrderNo($data, $order);

            if(!$this->handler($data, $order)) {
                return $this->onComplete($data, null, false);
            }

            // 订单状态改变等等全部在这里做
            order_success($order->id);
            return $this->onComplete($data, $order, true);
        } catch(\Exception $e) {
            return $this->onComplete($data, null, false);
        }
    }

    abstract protected function getData(Request $request);
    abstract protected function getOrderNo($data);
    abstract protected function getTradeOrderNo($data, Orders $order);
    abstract protected function verifySign($data, Orders $order);
    abstract protected function handler($data, Orders  $order);
    abstract protected function onComplete($data, Orders $order, $isSuccess, $code);
}