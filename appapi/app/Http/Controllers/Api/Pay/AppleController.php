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
use App\Model\OrderExtend;
use App\Model\UcuserInfo;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCoupon;
use App\Model\ZyCouponLog;
use App\Model\ForceCloseIaps;

class  AppleController extends Controller{

    /*
     * 验证苹果信息
     * */

    public function validateReceiptAction ()
    {
        $sn  = $this->request->input("order_id"); //生成订单的时候返回的订单号
        //获取当前的订单的ID
        $oid = $this->request->input("id"); //处理当前的操作
        //匹配当前的操作的实现
        $receipt = $this->request->input('receipt');
        $transaction = $this->parameter->tough("transaction_id");
        $receipt_a = urldecode($receipt);

        $mds = md5($receipt_a);
        $orders =  Orders::where("id",$oid)->where("sn",$sn)->first();

        if (!$orders || $orders->status != 0) return ["code"=>0,"msg"=> "订单不存在或已完成"];

        $logsql = "select id from ios_receipt_log WHERE receipt_md5 = '{$mds}'";
        $log_data = app('db')->select($logsql);
        if(count($log_data) && $orders->status != 0 ) return ["code"=>0,"msg"=>"订单已完成!"];

        //保存当前的操作
        $sql = "insert into ios_receipt_log (`receipt_md5`,`receipt_base64`) VALUES ('".$mds."','".$receipt."')";
        app('db')->select($sql);
        $appid = $this->parameter->tough("_appid");

        $sanboxsql  = "select c.*,p.psingKey from ios_application_config as c inner join procedures as p where c.app_id = {$appid} LIMIT 1";
        $sandat = app('db')->select($sanboxsql);
        $issandbox = ($sandat[0]->sandbox ==1)?true:false;
        //  log_info("sandbox....................>>>>>>>",$issandbox);
        //订单号
        $dat = $this->getReceiptData($receipt, $issandbox); //开启黑盒测试

        if(!preg_match("/^\d{1,10}$/",$oid))  return ["code"=>0,"msg"=>trans("messages.app_param_type_error")];

        if(isset($dat["errNo"]) && $dat["errNo"] ==0 && isset($dat['data']) &&  count($dat['data']) > 0){
            foreach($dat['data'] as $key =>$value){
                if($value['transaction_id'] == $transaction){
                    $o_ext = IosOrderExt::where("transaction_id",$transaction)->first();
                    if($o_ext) return ["code"=>0,"msg"=>"订单已完成."];
                    //购买成功写入数据库
                    $od = IosOrderExt::where("oid",$oid)->first();
                    $od ->transaction_id = $transaction;
                    $od ->descript = "SUCESS";
                    $ore = $od ->save();
                    if($ore){
                        $orders =  Orders::where("id",$oid)->where("sn",$sn)->first();
                        $orders->paymentMethod = "AppleStore";
                        $os = $orders->save(); //当前的信息是否保存成功！失败信息回归
                        if(!$os){
                            $od->transaction = '';
                            $od->descript = '';
                            $od->save();
                            return ["code"=>0,"msg"=>"订单处理失败."];
                        }
                        //通知发货
                        order_success($orders->id);
                        return ["code"=>1,"msg"=>trans("messages.apple_buy_success")];
                    }else{
                        throw new ApiException(ApiException::Remind,"订单处理失败!");
                    }
                }
            }
        }else{
            throw  new ApiException(ApiException::Remind,"订单验证失败");
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

        log_debug('ios_receipt', ['reqdata' => $postData, 'resdata' => $response], $endpoint);

        if ($errno != 0) {//curl请求有错误
            return trans("messages.request_time_out");
        }else{
            $data = json_decode($response, true);
            if (!is_array($data)) {
                return trans("messages.apple_rer_error_type");
            }

            //判断购买时候成功
            if (!isset($data['status']) || $data['status'] != 0) {
                return "验证订单状态不正确";
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
        $zone_id = $this->parameter->get('zone_id');
        $zone_name = $this->parameter->get('zone_name');
        $role_id = $this->parameter->get('role_id');
        $role_level = $this->parameter->get('role_level');
        $role_name = $this->parameter->get('role_name');
        $product_id = $this->parameter->tough('product_id');
        $appid  = $this->request->input("_appid");

        // 是否强制实名制
        if(($this->procedure_extend->enable & 0x0000000C) == 0x0000000C) {
            $user_info = UcuserInfo::from_cache($this->user->ucid);
            if(!$user_info || !$user_info->card_no) {
                throw new ApiException(ApiException::NotRealName, trans('messages.check_in_before_pay'));
            }
        }

        $ord = Orders::where("ucid",$ucid)->where('vorderid',$vorderid)->get();

        if(count($ord)) throw new ApiException(ApiException::Remind,trans('messages.order_not_exists')); //限制关闭

        $sql = "select p.fee,p.product_name,con.notify_url,con.notify_url_4,con.iap,con.bundle_id from ios_products as p LEFT JOIN ios_application_config as con ON p.app_id = con.app_id WHERE p.product_id = '{$product_id}' AND p.app_id = {$appid}";
        $dat = app('db')->select($sql);
        if(count($dat) == 0) throw new ApiException(ApiException::Remind,trans('messages.product_not_exists'));

        //验证当前的发货信息
        if(!check_url($dat[0]->notify_url) && !check_url($dat[0]->notify_url_4)) throw new ApiException(ApiException::Remind,trans('messages.notifyurl_error'));
        if($dat[0]->bundle_id =='' || !isset($dat[0]->iap)) throw new ApiException(ApiException::Remind,trans('messages.bundle_ipa_not_exists'));
        //验证信息结束
        $order = new Orders;
        $order->getConnection()->beginTransaction();
        $order->ucid = $ucid;
        $order->uid = $uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->notify_url = $dat[0]->notify_url_4 ?: $dat[0]->notify_url;
        $order->vorderid = $vorderid;
        $order->fee = $dat[0]->fee;
        $order->subject = $dat[0]->product_name;
        $order->body = $dat[0]->product_name;//"role_name:" . $role_name . "zone_name:" . $zone_name;
        $order->createIP = getClientIp();
        $order->status = Orders::Status_WaitPay;
        $order->paymentMethod = '';
        $order->hide = false;
        $order->cp_uid = $this->session->cp_uid;
        $order->user_sub_id = $this->session->user_sub_id;
        $order->user_sub_name = $this->session->user_sub_name;
        $order->real_fee = $order->fee;
        $order->save();

        // order_extend;
        if($zone_id || $zone_name || $role_id || $role_level || $role_name) {
            $order_extend = new OrderExtend;
            $order_extend->oid = $order->id;
            $order_extend->ucid = $this->user->ucid;
            $order_extend->pid = $this->procedure->pid;
            $order_extend->date = date('Ymd');
            $order_extend->zone_id = $zone_id;
            $order_extend->zone_name = $zone_name;
            $order_extend->role_id = $role_id;
            $order_extend->role_level = $role_level;
            $order_extend->role_name = $role_name;
            $order_extend->save();
        }

        $ext = new IosOrderExt;
        $ext->oid = $order->id;
        $ext->product_id = $product_id;
        $ext->zone_name = $zone_name;
        $ext->role_name = $role_name;
        $ext->transaction_id = time();
        $ext->save();

        $order->getConnection()->commit();
        $order_is_first = $order->is_first();

        $iap = $dat[0]->iap;
        //查看当前的充值总金额
        if($iap == 1) {
            //  读取用户充值总额
            $force_close_iaps = ForceCloseIaps::whereRaw("find_in_set({$pid},  appids)")->where('closed', 0)->get();
            $appids = [];
            $iap_paysum = 0;
            foreach ($force_close_iaps as $v) {
                $appids = array_merge($appids, explode(',', $v->appids));
                $iap_paysum += $v->fee;
            }
            log_info("user_pay_iap_paysum==========>",$iap_paysum);
            if ($iap_paysum > 0) {
                $paysum = Orders::whereIn('vid', array_unique($appids))->where('status', '!=', Orders::Status_WaitPay)->where('ucid', $this->user->ucid)->sum('fee');
                log_info("user_pay==========>",$paysum."_".$this->user->ucid);
                if ($paysum >= $iap_paysum) {
                    $iap = 0;
                }
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

            $e = $rule->is_valid($pid);
            if($e === false) continue;

            $list[] = [
                'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 1, 'fee' => $fee, 'id' => $v->vcid, 'e' => $e])),
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

            $e = $rule->is_valid($pid, $order->fee, $order_is_first);
            if($e === false) continue;

            $list[] = [
                'id' => encrypt3des(json_encode(['oid' => $order->id, 'type' => 2, 'fee' => $fee, 'id' => $v->id, 'e' => $e])),
                'fee' => $fee,
                'name' => $rule->name,
            ];
        }
        $user_info = UcuserInfo::from_cache($this->user->ucid);

        return [
            'order_id' => $order->sn,
            'id'      =>$order->id,//返回当前的订单
            'fee' => $dat[0]->fee,
            "iap" =>$iap ,//支付的方式1 ios 0为第三方支付
            'way' => [1, 2, 3],
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'balance' => $this->user->balance,
            'coupons' => $list,
            'package' => $dat[0]->bundle_id,
            'product_name'=> $dat[0]->product_name,
        ];


    }

    //返回当前的限制的控制
    public function AppleLimitAction(){

        $ucid = $this->user->ucid;
        $appid  = $this->request->input("_appid");
        $sql = "select con.iap from ios_products as p LEFT JOIN ios_application_config as con ON p.app_id = con.app_id WHERE  p.app_id = {$appid}";
        $dat = app('db')->select($sql);
        if(count($dat) == 0) throw new ApiException(ApiException::Remind,"not exists!");
        $pay_type = is_numeric($dat[0]->iap) ? $dat[0]->iap : 1;//is_numeric($dat[0]->iap) ? 0 : $dat[0]->iap;
        //查看当前的充值总金额
        if($pay_type == 1){
            $force_close_iaps = ForceCloseIaps::whereRaw("find_in_set({$appid},  appids)")->where('closed', 0)->get();
            $appids = [];
            $iap_paysum = 0;
            foreach ($force_close_iaps as $v) {
                $appids = array_merge($appids, explode(',', $v->appids));
                $iap_paysum += $v->fee;
            }
            log_info("user_pay_iap_paysum==========>",$iap_paysum);
            if ($iap_paysum > 0) {
                $paysum = Orders::whereIn('vid', array_unique($appids))->where('status', '!=', Orders::Status_WaitPay)->where('ucid', $ucid)->sum('fee');
                log_info("user_pay==========>",$paysum."_".$this->user->ucid);
                if ($paysum >= $iap_paysum) {
                    $pay_type = 0;
                }
            }
        }
        return ["paytype"=>$pay_type];
    }
}
