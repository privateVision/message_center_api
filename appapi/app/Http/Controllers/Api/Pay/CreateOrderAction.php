<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\UcuserInfo;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCouponLog;
use App\Model\ZyCoupon;
use App\Model\ProceduresProducts;

trait CreateOrderAction {

    public function NewAction() {
        $zone_id = $this->parameter->get('zone_id');
        $zone_name = $this->parameter->get('zone_name');
        $role_id = $this->parameter->get('role_id');
        $role_level = $this->parameter->get('role_level');
        $role_name = $this->parameter->get('role_name');
        $vorderid = $this->parameter->tough('vorderid');
        $notify_url = $this->parameter->tough('notify_url');

        // product_id or fee,body,subject
        $product_id = $this->parameter->get('protected_id');
        if(!$product_id) {
            $fee = $this->parameter->get('fee');
            $body = $this->parameter->get('body');
            $subject = $this->parameter->get('subject');
        } else {
            $product = ProceduresProducts::where('cp_product_id', $product_id);

            if(!$product) throw new ApiException(ApiException::Error, '计费点不存在'); // LANG:product_not_exists

            $fee = $product->fee / 100;
            $body = $product->name;
            $subject = $product->desc;
        }
        
        $pid = $this->procedure->pid;
        
        // 是否强制实名制
        if(($this->procedure_extend->enable & 0x0000000C) == 0x0000000C) { /*[3:2]*/
            $user_info = UcuserInfo::from_cache($this->user->ucid);
            if(!$user_info || !$user_info->card_no) {
                throw new ApiException(ApiException::NotRealName, '帐号未实名制，无法支付，请先实名后再操作'); // LANG:not_pay_before_reg
            }
        }

        $order = new Orders;
        $order->getConnection()->beginTransaction();

        $order->ucid = $this->user->ucid;
        $order->uid = $this->user->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->createIP = $this->parameter->get('_ipaddress', null) ?: $this->request->ip();
        $order->status = Orders::Status_WaitPay;
        $order->cp_uid = $this->session->cp_uid;
        $order->user_sub_id = $this->session->user_sub_id;
        $order->user_sub_name = $this->session->user_sub_name;
        $order->fee = $fee;
        $order->body = $body;
        $order->subject = $subject;
        $order->notify_url = $notify_url;
        $order->vorderid = $vorderid;
        //$order->real_fee = $order->fee;
        //$order->paymentMethod = '';
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

        $order->getConnection()->commit();

        $order_is_first = $order->is_first();
        
        // 储值卡，优惠券
        $list = [];
        
        // 可用的支付方式
        $pay_methods = [];
        
        // 官方支付不允许使用储值卡或优惠券
        if(($this->procedure_extend->pay_method & 0x01) == 0) {
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
            
            $pay_methods_config = config('common.pay_methods');

            for($i = 1; $i < 32; $i++) {
                $_i = 1 << $i;
                if(($this->procedure_extend->pay_method & $_i) != 0 && isset($pay_methods_config[$_i])) {
                    $pay_methods[] = $pay_methods_config[$_i]['type'];
                }
            }
        }

        $user_info = UcuserInfo::from_cache($this->user->ucid);

        return [
            'order_id' => $order->sn,
            'fee' => $order->fee,
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'balance' => $this->user->balance,
            'coupons' => $list,
            'iap' => ($this->procedure_extend->pay_method& 0x01) == 0,
            'pay_methods' => $pay_methods,
        ];
    }

    /**
     * 在订单保存之前（对订单进行一些字段赋值等）
     * @param  Orders    $order     [description]
     * @return [type]               [description]
     */
    abstract protected function onCreateOrder(Orders $order);
}