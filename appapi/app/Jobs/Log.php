<?php

namespace App\Jobs;

use App\Model\MongoDB\AppApiLog;
use App\Model\IP2Location;
use App\Model\Area;

class Log extends Job
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->content['level'] === 'ERROR') {
            send_mail('SDK接口调用错误', explode('|', env('alarm_emails')), json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $logfile = env('log_path') . str_replace('-', '', substr($this->content['datetime'], 0, 10)) . '.log';

        $text = sprintf("%s %s.%d[%s] %s [%s]%s %s\n", 
            $this->content['datetime'], 
            $this->content['ip'], 
            $this->content['pid'], 
            $this->content['mode'], 
            $this->content['level'], 
            $this->content['keyword'], 
            $this->content['desc'], 
            json_encode($this->content['content'], JSON_UNESCAPED_UNICODE)
        );

        error_log($text, 3, $logfile);

        // 更新IP库
        $ip = $this->content['ip'];

        \App\Redis::mutex_lock('ip2location_' . $ip, function() use($ip) {
            if(!$ip || $ip == '0.0.0.1' || $ip == '127.0.0.1') return;

            $ip2location = IP2Location::find($ip);
            if(!$ip2location) {
                $ip2location = new IP2Location;
                $ip2location->ip = $ip;
            } elseif((time() - $ip2location->updated_ts) < 432000) { // 5天一更新
                return ;
            }

            $data = null;

            // 淘宝API
            $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
            $content = file_get_contents($url);
            log_debug('ip2location', ['resdata' => $content], $url);

            if($content) {
                $content = json_decode($content, true);
                if($content['code'] == 0) {
                    $content = $content['data'];

                    $data['country'] = $content['country'];
                    $data['country_id'] = $content['country_id'];
                    $data['area'] = $content['area'];
                    $data['area_id'] = $content['area_id'];
                    $data['region'] = $content['region'];
                    $data['region_id'] = $content['region_id'];
                    $data['city'] = $content['city'];
                    $data['city_id'] = $content['city_id'];
                    $data['county'] = $content['county'];
                    $data['county_id'] = $content['county_id'];
                    $data['isp'] = $content['isp'];
                    $data['isp_id'] = $content['isp_id'];
                }
            }

            // 新浪API
            if(!$data) {

                $url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip;
                $content = file_get_contents($url);
                log_debug('ip2location', ['resdata' => $content], $url);

                if($content) {
                    $content = json_decode($content, true);
                    if($content['ret'] == 1) {
                        $data['country'] = $content['country'];
                        $data['country_id'] = $content['country'] == '中国' ? 'CN' : '';

                        $area = Area::where('name', 'like', "%{$content['province']}%")->first();
                        if($area) {
                            $data['region'] = $area->name;
                            $data['region_id'] = $area->id;
                        }

                        $area = Area::where('name', 'like', "%{$content['city']}%")->first();
                        if($area) {
                            $data['city'] = $area->name;
                            $data['city_id'] = $area->id;
                        }

                        $data['isp'] = $content['isp'];
                    }
                }
            }

            if($data) {
                foreach($data as $k => $v) {
                    $ip2location->$k = $v;
                }

                $ip2location->updated_ts = time();

                $ip2location->save();
            }
        });
    }
}