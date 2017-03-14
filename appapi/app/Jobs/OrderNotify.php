<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Redis;
use App\Model\Orders;

class OrderNotify extends Job
{
    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    public function handle()
    {
        $order = Orders::find($this->order_id);
        if(!$order) return ;

        if(!$order->procedures) return ;
        if(!preg_match('/^https*:\/\/.*$/', $order->notify_url)) return ;

        $appkey = $order->procedures->psingKey;

        $data['uid'] = $order->uid;
        $data['ucid'] = $order->ucid;
        $data['body'] = $order->body;
        $data['subject'] = $order->subject;
        $data['fee'] = sprintf('%.2f',$order->fee);
        $data['vid'] = $order->vid;
        $data['sn'] = $order->sn;
        $data['vorderid'] = $order->vorderid;
        $data['createTime'] = strval($order->createTime);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= "{$k}={$v}&";
        }

        $str .= 'signKey='. $appkey;

        $data['sign'] = md5($str);

        $res = http_request($order->notify_url, $data);

        log_info('OrderNotify', ['url' => $order->notify_url, 'reqdata' => $data, 'resdata' => $res]);

        if(!$res) return $this->retry();

        $res = strtoupper($res);
        if($res != 'SUCCESS' && $res != 'OK') {
            return $this->retry();
        }
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