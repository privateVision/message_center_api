<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/7
 * Time: 9:15
 */

namespace App\Http\Controllers\Api\Pay;

class OrderChannelController extends Controller{

    /*
     * 渠道订单创建
     * @param pid int 游戏id
     * @param rid int 当前的渠道id
     * @param zone int 当前的区服
     * @param role string 当前的角色
     * @param vorderid string 厂家订单号
     * @param fee float  充值金额
     * @param thread uid int 第三方账户的uid
     * @return
     * */
    const SWITCH_TYPE = 1; //0:完全不使用afsdk,1:切支付,不切登录,2:只用afsdk

    public function OrderChannel(){

        $pid = $this->procedure->pid; //通用订单创建
        //上层添加API 时间请求次数限制
        $uid = $this->user->uid;
        $ucid = $this->user->ucid;
        $vorderid = $this->parameter->tough('vorderid'); //厂家订单id
        $zone_name = $this->parameter->tough("zone");
        $role_name = $this->parameter->tough('role');
        $product_id = $this->parameter->tough('product_id');//产品地
        $appid  = $this->request->input("_appid");

        $pnum = $this->request->input("productCount")?$this->request->input("productCount"):1; //购买数量
        //渠道
        $rid = $this->parameter->input("_vid"); //渠道ID

        $ord = Orders::where("ucid",$ucid)->where('vorderid',$vorderid)->get();

        if(count($ord)) return "had exists"; //限制关闭
        try {
            $sql = "";
            $dat = app('db')->select($sql);

            if(count($dat) == 0) throw new ApiException(ApiException::Remind,"not exists!");

            $order = new Orders;
            $order->getConnection()->beginTransaction();
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

            $order->cp_uid = $this->session->cp_uid;
            $order->user_sub_id = $this->session->user_sub_id;
            $order->user_sub_name = $this->session->user_sub_name;
            $order->real_fee = $order->fee;
            $order->save();

            $order->getConnection()->commit();
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
                "pay_type" =>$pay_type ,//是否开启支付限制
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