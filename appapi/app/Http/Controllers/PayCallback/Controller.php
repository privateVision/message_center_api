<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Model\Orders;
use App\Model\OrderExtend;
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
                return $this->onComplete($data, $order, false);
            }

            // 记录回调
            $callback_log = new CallbackLog;
            $callback_log->timestamp = date('Y-m-d H:i:s');
            $callback_log->order_id = $order->sn;
            $callback_log->postdata = http_build_query($data);
            $callback_log->asyncSave();
            
            if($order->status != Orders::Status_WaitPay) {
                log_error('paycallback_error', ['sn' => $sn], '订单状态不正确');
                return $this->onComplete($data, $order, true);
            }

            $order->callback_ts = time();
            $order->save();

            $outer_order_no = $this->getTradeOrderNo($data, $order);

            // 记录订单信息
            $order_extend = OrderExtend::find($order->id);
            if(!$order_extend) $order_extend = new OrderExtend();
            $order_extend->third_order_no = $outer_order_no;
            $order_extend->extra_params = json_encode($data);
            $order_extend->real_fee = $order->real_fee * 100;
            $order_extend->asyncSave();

            if(!$this->handler($data, $order)) {
                return $this->onComplete($data, $order, false);
            }

            // 订单状态改变等等全部在这里做
            order_success($order->id);
            return $this->onComplete($data, $order, true);
        } catch(\Exception $e) {
            log_error('error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'path' => $request->path(),
                'reqdata' => $request->all()
            ]);

            return $this->onComplete($data, $order, false);
        }
    }

    /**
     * 获取回调的所有数据
     * @param  Request $request
     */
    abstract protected function getData(Request $request);

    /**
     * 获取我方订单号
     * @param mixed $data getData方法返回的数据
     */
    abstract protected function getOrderNo($data);

    /**
     * 获取第三方订单号（微信，支付宝等）
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    abstract protected function getTradeOrderNo($data, $order);

    /**
     * 验证签名
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    abstract protected function verifySign($data, $order);

    /**
     * 订单特殊处理逻辑判断，如果返回false则视为订单支付失败
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    abstract protected function handler($data, $order);

    /**
     * 订单完成后的回应，返回值将直接输出给回调方
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @param  boolean $isSuccess 订单是否处理成功
     */
    abstract protected function onComplete($data, $order, $isSuccess);
}