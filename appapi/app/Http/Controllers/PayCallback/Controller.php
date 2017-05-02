<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\Orders;
use App\Model\CallbackLog;

abstract class Controller extends \App\Controller
{
    
    public function CallbackAction(Request $request) {
        try {
            $data = $this->getData($request);
            $sn = $this->getOrderNo($data); 

            log_info('paycallback', ['route' => $request->path(), 'data' => $data, 'sn' => $sn]);

            $order = null;
        
            if($sn) {
                $order = Orders::from_cache_sn($sn);
            }

            if(!$order) {
                log_error('paycallback_error', null, '订单不存在');
                return $this->onComplete($data, null, true);
            }
            
            if(!$this->verifySign($data, $order)) {
                log_error('paycallback_error', null, '签名验证失败');
                return $this->onComplete($data, null, false);
            }

            $callback_log = new CallbackLog;
            $callback_log->timestamp = date('Y-m-d H:i:s');
            $callback_log->order_id = $order->sn;
            $callback_log->postdata = http_build_query($data);
            $callback_log->asyncSave();
            
            if($order->status != Orders::Status_WaitPay) {
                log_error('paycallback_error', ['sn' => $sn], '订单状态不正确');
                return $this->onComplete($data, null, true);
            }

            //open_online: 线上没这个字段
            //$order->callback_ts = time();
            //$order->save();

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
    abstract protected function getTradeOrderNo($data, $order);
    abstract protected function verifySign($data, $order);
    abstract protected function handler($data, $order);
    abstract protected function onComplete($data, $order, $isSuccess);
}