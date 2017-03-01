<?php
namespace App\Controller\External;

use App\Exceptions\PayCallbackException;
use Illuminate\Http\Request;
use App\Model\Orders;

abstract class Controller extends \App\Controller
{
    
    public function CallbackAction(Request $request) {
        try {
            $data = $this->getData($request);

            $sn = $this->getOrderNo($data); 
            $order = null;
        
            if($sn) {
                $order = Orders::where('sn', $sn)->first();
            }

            if(!$order) {
                throw new PayCallbackException(PayCallbackException::OrderNotExists, "订单不存在");
            }
            
            if($order->status == Orders::Status_Success) {
                throw new PayCallbackException(PayCallbackException::OrderStatusError, '订单状态不正确');
            }
            
            if(!$this->verifySign($data, $order)) {
                throw new PayCallbackException(PayCallbackException::SignError, "签名错误");
            }

            $outer_order_no = $this->getOuterOrderNo($data, $order);

            if(!$this->handler($data, $order)) {
                throw new PayCallbackException(PayCallbackException::HandleError, "订单处理失败");
            }

            // 订单状态改变等等全部在这里做
            order_success($order->id);

            $code = PayCallbackException::Success;
        } catch(PayCallbackException $e) {
            $code = $e->getCode();
        } catch(\Exception $e) {
            $code = PayCallbackException::SystemError;
        }

        $isSuccess = in_array($code, [PayCallbackException::Success, PayCallbackException::OrderNotExists, PayCallbackException::OrderStatusError]);
        
        return $this->onComplete($data, $order, $isSuccess);
    }

    abstract protected function getData(Request $request);
    abstract protected function getOrderNo($data);
    abstract protected function getOuterOrderNo($data, Orders $order);
    abstract protected function verifySign($data, Orders $order);
    abstract protected function handler($data, Orders  $order);
    abstract protected function onComplete($data, Orders $order, $isSuccess, $code);
}