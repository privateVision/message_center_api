<?php
namespace App\Http\Controllers\PayCallback;

use Illuminate\Http\Request;
use App\Exceptions\Exception;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\CallbackLog;

/**
 * Class Controller
 * 继承该类可实现支付回调，任何处理逻辑出现错误需抛出错误为\App\Exceptions\Exception，其中code=0订单处理失败，code=1订单支付成功
 * @package App\Http\Controllers\PayCallback
 */

abstract class Controller extends \App\Controller
{

    public function CallbackAction(Request $request) {
        try {
            $data = $this->getData($request);
            $sn = $this->getOrderNo($data);

            log_info('paycallback', ['route' => $request->path(), 'data' => $data, 'sn' => $sn]);

            $order = $order_extend = null;

            if ($sn) {
                $order = Orders::from_cache_sn($sn);
            }

            if (!$order) {
                throw new Exception(trans('messages.order_not_exists'), 1);
            }

            $order_extend = OrderExtend::find($order->id);
            if (!$order_extend) { // XXX 理论上是不可能不存在的（4.0可能不存在）
                $order_extend = new OrderExtend();
                $order_extend->oid = $order->id;
            }

            $order_extend->extra_params = ['callback' => $data];
            $order_extend->asyncSave();

            if (!$this->verifySign($data, $order, $order_extend)) {
                throw new Exception(trans('messages.sign_error'), 0);
            }

            // 记录回调
            $callback_log = new CallbackLog;
            $callback_log->timestamp = date('Y-m-d H:i:s');
            $callback_log->order_id = $order->sn;
            $callback_log->postdata = http_build_query($data);
            $callback_log->asyncSave();

            if ($order->status != Orders::Status_WaitPay) {
                throw new Exception(trans('messages.order_status_error'), 1);
            }

            $order->callback_ts = time();
            $order->save();

            // XXX 记录订单信息
            $order_extend->third_order_no = $this->getTradeOrderNo($data, $order, $order_extend);

            if (!$this->handler($data, $order, $order_extend)) {
                throw new Exception(trans('messages.order_handle_fail'), 0);
            }

            $order_extend->extra_params = ['is_success' => true];

            // 订单状态改变等等全部在这里做
            order_success($order->id);

            return $this->resdata($data, $order, $order_extend, true);
        } catch(Exception $e) {
            return $this->resdata($data, $order, $order_extend, $e->getCode() == 1, $e->getMessage());
        } catch(\Exception $e) {
            log_error('error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'path' => $request->path(),
                'reqdata' => $request->all()
            ]);

            return $this->resdata($data, $order, $order_extend, false, trans('messages.order_handle_fail'));
        }
    }

    private function resdata($data, $order, $order_extend, $is_success, $message = null) {
        $response = $this->onComplete($data, $order, $order_extend, $is_success);

        if($order_extend && $order_extend->callback) {
            if(preg_match('/^https*:/', $order_extend->callback)) {
                if(strpos($order_extend->callback, '?') === false) {
                    $baseurl = $order_extend->callback . '?';
                } else {
                    $baseurl = $order_extend->callback . '&';
                }

                if($order) {
                    return header('Location:' . $baseurl . http_build_query([
                            'is_success' => $is_success ? 1 : 0,
                            'message' => $message ? $message : $response,
                            'openid' => $order->cp_uid ? $order->cp_uid : $order->ucid,
                            'order_no' => $order->sn,
                            'trade_order_no' => $order->vorderid,
                        ]));
                } else {
                    return header('Location:' . $baseurl . http_build_query([
                            'is_success' => $is_success ? 1 : 0,
                            'message' => $message ? $message : $response,
                            'openid' => '',
                            'order_no' => '',
                            'trade_order_no' => '',
                        ]));
                }
            } else {
                if($order) {
                    return view('pay_callback/callback', [
                        'callback' => $order_extend->callback,
                        'is_success' => $is_success,
                        'message' => $message ? $message : $response,
                        'openid' => $order->cp_uid ? $order->cp_uid : $order->ucid,
                        'order_no' => $order->sn,
                        'trade_order_no' => $order->vorderid,
                    ]);
                } else {
                    return view('pay_callback/callback', [
                        'callback' => $order_extend->callback,
                        'is_success' => $is_success,
                        'message' => $message ? $message : $response,
                        'openid' => null,
                        'order_no' => null,
                        'trade_order_no' => null,
                    ]);
                }
            }
        } else {
            return $response;
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
    abstract protected function getTradeOrderNo($data, $order, $order_extend);

    /**
     * 验证签名
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    abstract protected function verifySign($data, $order, $order_extend);

    /**
     * 订单特殊处理逻辑判断，如果返回false则视为订单支付失败
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     */
    abstract protected function handler($data, $order, $order_extend);

    /**
     * 订单完成后的回应，返回值将直接输出给回调方
     * @param   mixed $data getData方法返回的数据
     * @param  \App\Model\Orders $order Orders
     * @param  boolean $is_success 订单是否处理成功
     */
    abstract protected function onComplete($data, $order, $order_extend, $is_success);
}