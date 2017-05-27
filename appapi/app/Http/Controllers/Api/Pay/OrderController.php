<?php
namespace App\Http\Controllers\Api\Pay;

use App\Exceptions\ApiException;
use App\Model\UcuserInfo;
use App\Model\Orders;
use App\Model\OrderExtend;
use App\Model\UcusersVC;
use App\Model\VirtualCurrencies;
use App\Model\ZyCouponLog;
use App\Model\ZyCoupon;
use App\Model\ProceduresProducts;
use App\Model\ForceCloseIaps;

class OrderController extends Controller {

    public function ConfigAction() {
        $pid = $this->procedure->pid;

        // 是否开启官方支付
        $iap = (($this->procedure_extend->enable & (1 << 8)) == 0);

        if($iap) {
            // 读取用户充值总额
            $force_close_iaps = ForceCloseIaps::whereRaw("find_in_set({$pid}, appids)")->where('closed', 0)->get();
            $appids = [];
            $iap_paysum = 0;
            foreach ($force_close_iaps as $v) {
                $appids = array_merge($appids, explode(',', $v->appids));
                $iap_paysum += $v->fee;
            }

            if ($iap_paysum > 0) {
                $paysum = Orders::whereIn('vid', array_unique($appids))->where('status', '!=', Orders::Status_WaitPay)->where('ucid', $this->user->ucid)->sum('fee');

                if ($paysum >= $iap_paysum) {
                    $iap = false;
                }
            }
        }

        // 支付方式判断

        $pay_methods = [];

        if(!$iap) {
            $pay_methods_config = config('common.pay_methods');

            for($i = 0; $i <= 31; $i++) {
                $pay_method = @$pay_methods_config[floor($i/4)];
                if(!$pay_method) continue;

                if(($this->procedure_extend->pay_method & (1 << $i)) == 0) continue;

                if(in_array($i % 4, $pay_method['pay_type'])) {
                    $pay_method['pay_type'] = $i % 4;
                    $pay_methods[floor($i/4)] = $pay_method;
                }
            }
        }

        return [
            'iap' => $iap,
            'sandbox' => ($this->procedure_extend->enable & (1 << 7)) == 0,
            'pay_methods' => $pay_methods,
        ];
    }

    public function NewAction() {
        $zone_id = $this->parameter->get('zone_id', '');
        $zone_name = $this->parameter->get('zone_name', '');
        $role_id = $this->parameter->get('role_id', '');
        $role_level = $this->parameter->get('role_level', '');
        $role_name = $this->parameter->get('role_name', '');
        $product_type = $this->parameter->get('product_type', 0); // 0 游戏道具，1 F币，2 游币（H5专用）

        // XXX 旧版本如果在4.0以下，则通过此方式判断是否购买F币
        do {
            if($product_type == 1) break;
            if(version_compare('4.1', $this->parameter->tough('_version'), '>=')) break;

            if(!$this->parameter->get('vorderid') && !$this->parameter->get('notify_url')) {
                $product_type = 1;
            }
        } while(false);

        // 非购买F币需CP订单号
        $vorderid = '';
        if($product_type != 1) {
            $vorderid = $this->parameter->tough('vorderid');
        }

        $pid = $this->procedure->pid;

        // TODO 是否强制实名制，改成在发起支付时再判断
//        if(($this->procedure_extend->enable & 0x0000000C) == 0x0000000C) {
//            $user_info = UcuserInfo::from_cache($this->user->ucid);
//            if(!$user_info || !$user_info->card_no) {
//                throw new ApiException(ApiException::NotRealName, trans('messages.check_in_before_pay'));
//            }
//        }

        // 非购买F币没有通知地址就去数据库看有没有预设置
        $notify_url = $this->parameter->get('notify_url', '');
        if($product_type != 1) {
            if (!$notify_url) {
                $notify_url = $this->procedure_extend->pay_callback_url_4 ?: $this->procedure_extend->pay_callback_url_2;
            }

            if (!$notify_url) {
                throw new ApiException(ApiException::Remind, trans('messages.not_set_notify_url'));
            }
        }

        // 如果传了product_id则通过product_id找到计费点信息，否则fee,body,subject必传

        $product_id = $this->parameter->get('product_id', '');
        if(!$product_id) {
            $fee = $this->parameter->tough('fee');
            $body = $this->parameter->tough('body');
            $subject = $this->parameter->tough('subject');
        } else {
            $product = ProceduresProducts::where('cp_product_id', $product_id)->where('pid', $pid)->first();

            if(!$product) throw new ApiException(ApiException::Error, trans('messages.product_not_exists')); // LANG:product_not_exists

            $fee = $product->fee / 100;
            $body = strval($product->name);
            $subject = strval($product->desc);
        }


        $order = new Orders;
        $order->getConnection()->beginTransaction();

        $order->ucid = $this->user->ucid;
        $order->uid = $this->user->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->createIP = getClientIp();
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
        $order_extend->product_type = $product_type;
        $order_extend->product_id = isset($product) ? $product->id : 0;
        $order_extend->save();

        $order->getConnection()->commit();

        $order_is_first = $order->is_first();

        // 储值卡，优惠券
        $list = [];

        // 可用的支付方式
        $pay_methods = [];

        $iap = (($this->procedure_extend->enable & (1 << 8)) == 0);

        if($iap) {
            // 读取用户充值总额
            $force_close_iaps = ForceCloseIaps::whereRaw("find_in_set({$pid}, appids)")->where('closed', 0)->get();
            $appids = [];
            $iap_paysum = 0;
            foreach ($force_close_iaps as $v) {
                $appids = array_merge($appids, explode(',', $v->appids));
                $iap_paysum += $v->fee;
            }

            if ($iap_paysum > 0) {
                $paysum = Orders::whereIn('vid', array_unique($appids))->where('status', '!=', Orders::Status_WaitPay)->where('ucid', $this->user->ucid)->sum('fee');

                if ($paysum >= $iap_paysum) {
                    $iap = false;
                }
            }
        }

        // 非官方支付
        if(!$iap) {
            // 购买F币不允许使用储值卡或优惠券
            if($product_type != 1) {
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
            }

            // 从procedures_extend.pay_method读取可用的支付方式

            $pay_methods_config = config('common.pay_methods');

            for($i = 0; $i <= 31; $i++) {
                $pay_method = @$pay_methods_config[floor($i/4)];
                if(!$pay_method) continue;

                if(($this->procedure_extend->pay_method & (1 << $i)) == 0) continue;

                if(in_array($i % 4, $pay_method['pay_type'])) {
                    $pay_method['pay_type'] = $i % 4;
                    $pay_methods[floor($i/4)] = $pay_method;
                }
            }
        }

        $user_info = UcuserInfo::from_cache($this->user->ucid);

        return [
            'order_id' => $order->sn,
            'id' => $order->id, // XXX 4.0 苹果支付会用到
            'fee' => $order->fee,
            'vip' => $user_info && $user_info->vip ? (int)$user_info->vip : 0,
            'balance' => $this->user->balance,
            'coupons' => $list,
            'iap' => $iap,
            'pay_methods' => array_values($pay_methods),
            'package' => $this->procedure_extend->package_name,
        ];
    }

    public function InfoAction() {
        $sn = $this->parameter->tough('order_id');

        $order = Orders::from_cache_sn($sn);

        if($order) {
            return [
                'status' => $order->status
            ];
        }

        return [];
    }
}