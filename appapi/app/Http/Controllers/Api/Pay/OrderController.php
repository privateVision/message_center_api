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
use Illuminate\Http\Request;

class OrderController extends Controller {

    protected function getPayConfig() {
        $appversion = $this->parameter->get('_app_version'); // 本来是必传参数，但兼容旧代码，所以无法必传
        $pid = $this->procedure->pid;

        // 是否开启官方支付
        $iap = $this->procedure_extend->isIAP();

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

        // 第三方支付与官方支付并存
        if($this->procedure_extend->isTooUseIAP() && !$iap) {
            $pay_methods[] = [
                'type' => 'iap',
                'api' => '',
                'pay_type' => 0,
            ];
        }

        return [
            'iap' => $iap,
            'sandbox' =>  $appversion ? $this->procedure_extend->isSandbox($appversion) : false,
            'pay_methods' => array_values($pay_methods),
            'paytype' => $iap, // 兼容IOS4.0
        ];
    }

    public function ConfigAction() {
        return $this->getPayConfig();
    }

    /**
     * $product_type是怎么来的？
     * 在4.0的接口中有一个接口：api/pay/order/f/new，在4.1版本被废弃了，但是必需得让4.0的客户端还能正常调用该接口
     * 于是api/pay/order/f/new指向了现在这个action，路由为api/pay/order/{product_type}/new
     * 因此当product_type = 'f'时表示这是一个充值F币的订单
     */
    public function NewAction(Request $request, $product_type = null) {
        $zone_id = $this->parameter->get('zone_id', '');
        $zone_name = $this->parameter->get('zone_name', '');
        $role_id = $this->parameter->get('role_id', '');
        $role_level = $this->parameter->get('role_level', '');
        $role_name = $this->parameter->get('role_name', '');

        // XXX 4.0
        if($product_type !== 'f') {
            $product_type = $this->parameter->get('product_type', 0); // 0 游戏道具，1 F币，2 游币（H5专用）
        } else {
            $product_type = 1;
        }

        // 非购买F币需CP订单号
        $vorderid = '';
        if($product_type != 1) {
            $vorderid = $this->parameter->tough('vorderid');
        }

        $pid = $this->procedure->pid;

        // TODO 是否强制实名制，改成在发起支付时再判断
//        if(($this->procedure_extend->enable & (3 << 2)) == (3 << 2)) {
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

        // 储值卡，优惠券
        $list = [];

        $payconf = $this->getPayConfig();

        $iap = $payconf['iap'];
        // 非官方支付
        if(!$iap) {
            // 购买F币不允许使用储值卡或优惠券
            if($product_type != 1) {
                $order_is_first = $order->is_first();

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
            'pay_methods' => array_values($payconf['pay_methods']),
            'package' => strval($this->procedure_extend->package_name),
            'product_id' => isset($product) ? $product->third_product_id : '',
            'order_name' => $subject,
            'order_desc' => $body,
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