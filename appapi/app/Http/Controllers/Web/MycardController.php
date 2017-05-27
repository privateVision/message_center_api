<?php
namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;
use App\Model\OrderExt;
use App\Model\Orders;
use App\Model\OrderExtend;

class MycardController extends \App\Controller {

    public function QueryAction(Request $request) {
        $StartDateTime = $request->input('StartDateTime');
        $EndDateTime = $request->input('EndDateTime');
        $MyCardTradeNo = $request->input('MyCardTradeNo');

        if((!$StartDateTime || !$EndDateTime) && !$MyCardTradeNo) {
            return '';
        }

        if($StartDateTime && $EndDateTime) {
            $s = strtotime(str_replace('T', ' ', $StartDateTime));
            $e = strtotime(str_replace('T', ' ', $EndDateTime));
            if(!$s or !$e) {
                return '';
            }

            $sd = date('Ymd', $s);
            $ed = date('Ymd', $e);

            $order_extend = OrderExtend::where('pay_method', '-7')->where('date', '>=', $sd)->where('date', '<=', $ed)->get();
        } elseif($MyCardTradeNo) {
            $order_extend = OrderExtend::where('third_order_no', $MyCardTradeNo)->get();
        }

        if(count($order_extend) == 0) return '';

        $response = '';

        foreach($order_extend as $v) {
            if(!@$v->extra_params['is_success']) continue;

            $order = Orders::find($v->oid);

            $response .= @$v->extra_params['callback']['PaymentType'] ?: $v->extra_params['TradeQuery']['PaymentType'];
            $response .= ",";
            $response .= @$v->extra_params['TradeSeq'];
            $response .= ",";
            $response .= $v->third_order_no;
            $response .= ",";
            $response .= @$v->extra_params['callback']['FacTradeSeq'] ?: $v->extra_params['TradeQuery']['FacTradeSeq'];
            $response .= ",";
            $response .= $order->ucid;
            $response .= ",";
            $response .= @$v->extra_params['callback']['Amount'] ?: $v->extra_params['TradeQuery']['Amount'];
            $response .= ",";
            $response .= @$v->extra_params['callback']['Currency'] ?: $v->extra_params['TradeQuery']['Currency'];
            $response .= ",";
            $response .= date('Y-m-d\\TH:i:s', $order->callback_ts);
            $response .= "<BR>";
        }

        return $response;
    }

    public function RescueAction(Request $request) {
        $data = $request->input('DATA');

        if(!$data) {
            return 'fail';
        }

        $data = json_decode($data, true);
        if(!$data) {
            return 'fail';
        }

        if(@$data['ReturnCode'] != 1) {
            return 'ReturnCodeNot_1';
        }

        $config = config('common.payconfig.mycard');
        if(getClientIp() !== $config['TradeQueryHost']) {
            // TODO 暂时关闭限制
            //return 'HostNotAllow';
        }

        $response = '';

        foreach($data['FacTradeSeq'] as $FacTradeSeq) {
            $order = Orders::where('sn', $FacTradeSeq)->first();
            if(!$order) {
                $response .= $FacTradeSeq . ':OrderNotExists,';
                continue;
            }

            if ($order->status != Orders::Status_WaitPay) {
                $response .= $FacTradeSeq . ':OrderAlreadySuccess,';
                continue;
            }

            $order_extend = OrderExtend::find($order->id);
            if(!$order_extend) {
                $response .= $FacTradeSeq . ':OrderExtendNotExists,';
                continue;
            }

            if(!isset($order_extend->extra_params['AuthCode'])) {
                $response .= $FacTradeSeq . ':AuthCodeNotExists,';
                continue;
            }

            // 确认交易
            $resdata = ['AuthCode' => $order_extend->extra_params['AuthCode']];
            $result = http_request($config['TradeQuery'], $resdata, true);
            log_debug('mycardTradeQuery', ['result' => $result], $config['TradeQuery']);

            if(!$result) {
                $response .= $FacTradeSeq . ':TradeQueryFail,';
                continue;
            }

            $result = json_decode($result, true);
            if(!$result) {
                $response .= $FacTradeSeq . ':TradeQueryFail,';
                continue;
            }

            if(@$result['ReturnCode'] != 1 && @$result['PayResult'] != 3) {
                $response .= $FacTradeSeq . sprintf(':TradeQuery_ReturnCode_%s_PayResult_%s', @$result['ReturnCode'], @$result['PayResult']);
                continue;
            }

            // 开始请款交易
            $result = http_request($config['PaymentConfirm'], $resdata, true);
            log_debug('mycardPaymentConfirm', ['result' => $result], $config['PaymentConfirm']);

            if(!$result) {
                $response .= $FacTradeSeq . ':PaymentConfirmFail,';
                continue;
            }

            $result = json_decode($result, true);
            if(!$result) {
                $response .= $FacTradeSeq . ':PaymentConfirmFail,';
                continue;
            }

            if(@$result['ReturnCode'] != 1) {
                $response .= $FacTradeSeq . sprintf(':PaymentConfirm_ReturnCode_%s', @$result['ReturnCode']);
                continue;
            }

            $order->callback_ts = time();
            $order->save();

            $order_extend->third_order_no = @$result['MyCardTradeNo'];
            $order_extend->extra_params = ['TradeSeq' => $result['TradeSeq'], 'TradeQuery' => $result, 'is_success' => true];
            $order_extend->asyncSave();

            order_success($order->id);

            $response .= $FacTradeSeq . ':success,';
        }

        return $response;
    }
}