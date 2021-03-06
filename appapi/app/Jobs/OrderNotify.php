<?php
namespace App\Jobs;

use App\Model\Orders;
use App\Model\Procedures;

class OrderNotify extends Job
{
    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    public function handle() {
        $order = Orders::from_cache($this->order_id);
        if(!$order || $order->status != Orders::Status_Success) return ; // 不是支付成功不允许发货

        if(!preg_match('/^https*:\/\/.*$/', $order->notify_url)) return ;

        $procedures = Procedures::from_cache($order->vid);
        if(!$procedures) return ;

        $appkey = $procedures->psingKey;

        $data['open_id'] = $order->cp_uid ? $order->cp_uid : $order->ucid;
        // $data['ucid'] = $order->cp_uid ? $order->cp_uid : $order->ucid; // todo: 兼容旧系统
        $data['body'] = $order->body;
        $data['subject'] = $order->subject;
        $data['fee'] = sprintf('%.2f',$order->fee);
        $data['vid'] = $order->vid;
        $data['sn'] = $order->sn;
        $data['vorder_id'] = $order->vorderid;
        $data['create_time'] = strval($order->createTime);
        $data['version'] = '4.0';
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= "{$k}={$v}&";
        }

        $str .= 'sign_key='. $appkey;
        $data['sign'] = md5($str);
        
        //$data['sign'] =  md5(http_build_query($data) ."&sign_key={$appkey}");

        $res = http_curl($order->notify_url, $data, false, array(), 'str');

        log_info('OrderNotify', ['url' => $order->notify_url, 'reqdata' => $data, 'resdata' => $res, 'signstr' => $str]);

        if(!$res) return $this->retry();

        $res = strtoupper($res);
        if($res != 'SUCCESS' && $res != 'OK') {
            return $this->retry();
        }

        $order->notify_ts = time();
        $order->status = Orders::Status_NotifySuccess;
        $order->save();
    }

    protected function retry() {
        $t = $this->attempts();
        $interval = [1=>5,5,10,10,30,30,60,60,60,300,300,300,600,600,600,600,1800,1800,3600,3600,7200,7200,14400,21400,21400];
        if(isset($interval[$t])) {
            return $this->release($interval[$t]);
        }

        return ;
    }
}