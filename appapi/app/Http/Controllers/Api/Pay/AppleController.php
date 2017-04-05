<?php
/**
 * Created by PhpStorm.
 * Ucuser: Administrator
 * Date: 2017/3/21
 * Time: 14:40
 */
namespace App\Http\Controllers\Api\Pay ;

use App\Exceptions\ApiException;
use App\Model\IosOrderExt;
use App\Model\Orders;
use App\Model\UcuserInfo;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCoupon;
use App\Model\ZyCouponLog;
use App\Model\OrderExtend;



class  AppleController extends Controller{

    /*
     * 验证苹果信息
     * */

    public function validateReceiptAction ()
    {
        //匹配当前的操作的实现
        $receipt = $this->request->input('receipt');
        $transaction = $this->parameter->tough("transaction_id");
        $receipt = urldecode($receipt);

        $logsql = "select id from ios_receipt_log WHERE receipt_md5 = ".md5($receipt);
        $log_data = app('db')->select($logsql);
        if(count($log_data)) throw new ApiException(ApiException::Remind,"had in ");

        //保存当前的操作

        $sql = "insert into ios_receipt_log (`receipt_md5`,`receipt_base64`) VALUES ({md5($receipt)},$receipt)";
        app('db')->select($sql);

        //订单号
        $sn  = $this->request->input("order_id"); //生成订单的时候返回的订单号

        $dat = $this->getReceiptData($receipt, true); //开启黑盒测试
        //获取当前的订单的ID
        $oid = $this->request->input("id"); //处理当前的操作

        if(!preg_match("/^\d{1,10}$/",$oid)) return trans("messages.app_param_type_error");


        if($dat["errNo"] ==0 && count($dat['data']) > 0){
            foreach($dat['data'] as $key =>$value){
                if($value->transaction_id == $transaction){
                    //购买成功写入数据库
                    $od = IosOrderExt::where("oid",$oid)->frist();
                    $od ->transaction_id = $transaction;
                    $od ->descript = "SUCESS";
                    $ore = $od ->save();
                    if($ore){
                        $orders =  Orders::where("id",$oid)->where("sn",$sn)->frist();
                        $orders ->status = 1;
                        $orders->paymentMethod = "AppleStore";
                        $os = $orders->save(); //当前的信息是否保存成功！失败信息回归
                        if(!$os){
                            $od->transaction = '';
                            $od->descript = '';
                            $od->save();
                            return trans("messages.app_buy_faild");
                        }
                        //通知发货
                        order_success($orders->id);
                        return trans("messages.apple_buy_success");
                    }else{
                        throw new ApiException(ApiException::Remind,trans("messages.app_buy_faild"));
                    }
                }
            }
        }else{
            throw  new ApiException(ApiException::Remind,trans("messages.app_buy_faild"));
        }

        //订单完成，通知发货，添加日志记录
    }
    //验证
    protected function getReceiptData($receipt, $isSandbox = false) {
        if ($isSandbox) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';//沙箱地址
        } else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';//真实运营地址
        }
        $postData = json_encode(
            array('receipt-data' => $receipt)
        );
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        //$errmsg   = curl_error($ch);

        curl_close($ch);

        if ($errno != 0) {//curl请求有错误
            return trans("messages.request_time_out");
        }else{
            $data = json_decode($response, true);
            if (!is_array($data)) {
                return trans("messages.apple_rer_error_type");
            }
            return $data;
            //判断购买时候成功
            if (!isset($data['status']) || $data['status'] != 0) {
                return trans("messages.app_buy_faild");
            }
            //返回产品的信息
            $order = [];
            $order['data'] = $data['receipt']['in_app'];
            $order['errNo'] = 0;
            return $order;
        }
    }

    /*
     * 苹果订单的创建
     * */

    public function OrderCreateAction(){
        $pid = $this->procedure->pid;

        //上层添加API 时间请求次数限制
        $uid = $this->user->uid;
        $ucid = $this->user->ucid;
        $vorderid = $this->parameter->tough('vorderid'); //厂家订单id
        $zone_name = $this->parameter->tough("zone_name");
        $role_name = $this->parameter->tough('role_name');
        $product_id = $this->parameter->tough('product_id');
        $appid  = $this->request->input("_appid");

        $ord = Orders::where("ucid",$ucid)->where('vorderid',$vorderid)->get();

        if(count($ord)) return "had exists"; //限制关闭
        try {
            $sql = "select p.fee,p.product_name,con.notify_url,con.iap from ios_products as p LEFT JOIN ios_application_config as con ON p.app_id = con.app_id WHERE p.product_id = '{$product_id}' AND p.app_id = {$appid}";
            $dat = app('db')->select($sql);
            if(count($dat) == 0) throw new ApiException(ApiException::Remind,"not exists!");

            $order = new Orders;
            $order->ucid = $ucid;
            $order->uid = $uid;

            $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $order->vid = $this->procedure->pid;
            $order->notify_url = $dat[0]->notify_url;
            $order->vorderid = $vorderid;
            $order->fee = $dat[0]->fee;
            $order->subject = $dat[0]->product_name;
            $order->body = "role_name:" . $role_name . "zone_name:" . $zone_name;
            $order->createIP = $this->request->ip();
            $order->status = Orders::Status_WaitPay;
            $order->paymentMethod = Orders::Way_Unknow;
            $order->hide = false;
            $order->save();

            $ext = new IosOrderExt;
            $ext->oid = $order->id;
            $ext->product_id = $product_id;
            $ext->zone_name = $zone_name;
            $ext->role_name = $role_name;
            $ext->transaction_id = time();
            $oext = $ext->save();

            $order_extend = new OrderExtend;
            $order_extend->order_id = $order->id;
            $order_extend->real_fee = 0;
            $order_extend->cp_uid = $this->session->cp_uid;
            $order_extend->save();

            $order_is_first = $order->is_first();

            $pay_type = $dat[0]->iap;
            //查看当前的充值总金额
            if($pay_type == 1){
                $sum = Orders::where("ucid",$ucid)->where("status",1)->sum('fee');
                //获取限额
                $sl = "select id,appids,fee from force_close_iaps where closed=0 AND {$appid} IN (appids)";
                $d = app('db')->select($sl);
                if(count($d)){
                    $pay_type =  ($sum > $d[0]->fee)?0:1;
                }
            }


            // 储值卡，优惠券
            $list = [];
            $result = UcusersVC::where('ucid', $this->user->ucid)->get();

            foreach($result as $v) {
                $fee = $v->balance * 100;
                if(!$fee) continue;

                $rule = VirtualCurrencies::from_cache($v->vcid);
                if(!$rule) continue;

                if(!$rule->is_valid($pid)) continue;

                $list[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 1, 'fee' => $fee, 'id' => $v->vcid])),
                    'fee' => $fee,
                    'name' => $rule->vcname,
                ];
            }

            $result = ZyCouponLog::where('ucid', $this->user->ucid)->where('is_used', false)->whereIn('pid', [0, $pid])->get();
            foreach($result as $v) {
                $rule = ZyCoupon::from_cache($v->coupon_id);
                if(!$rule) continue;

                $fee = $rule->money;
                if(!$fee) continue;

                if(!$rule->is_valid($pid, $order->fee, $order_is_first)) continue;

                $list[] = [
                    'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 2, 'fee' => $fee, 'id' => $v->id])),
                    'fee' => $fee,
                    'name' => $rule->name,
                ];
            }

            $user_info = UcuserInfo::from_cache($this->user->ucid);

            return [
                'order_id' => $order->sn,
                'id'      =>$order->id,//返回当前的订单
                'fee' => $dat[0]->fee,
                "iap" =>$pay_type ,//支付的方式1 ios 0为第三方支付
                'way' => [1, 2, 3],
                'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
                'balance' => $this->user->balance,
                'coupons' => $list,
            ];
        }catch(\Exception $e){
            echo $e->getMessage();
        }

    }





}
