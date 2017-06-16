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
        
        if(!$ip || $ip == '0.0.0.0' || $ip == '127.0.0.1') return;

        \App\Redis::mutex_lock('ip2location_' . $ip, function() use($ip) {
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
            $content = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 1]]));
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
                    $data['city_id'] = $content['city_id'] > 0 ? $content['city_id'] : NULL;
                    $data['county'] = $content['county'];
                    $data['county_id'] = $content['county_id'] > 0 ? $content['county_id'] : NULL;
                    $data['isp'] = $content['isp'];
                    $data['isp_id'] = $content['isp_id'] > 0 ? $content['isp_id'] : NULL;;
                }
            }

            // 新浪API
            if(!$data) {
                $url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip;
                $content = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 1]]));
                log_debug('ip2location', ['resdata' => $content], $url);

                if($content) {
                    $content = json_decode($content, true);
                    if($content['ret'] == 1) {
                        $data['country'] = $content['country'];
                        $data['country_id'] = $content['country'] == '中国' ? 'CN' : '';

                        if($content['province']) {
                            $area = Area::where('name', 'like', "%{$content['province']}%")->first();
                            if($area) {
                                $data['region'] = $area->name;
                                $data['region_id'] = $area->id;
                            }
                        }

                        if($content['city']) {
                            $area = Area::where('name', 'like', "%{$content['city']}%")->first();
                            if($area) {
                                $data['city'] = $area->name;
                                $data['city_id'] = $area->id;
                            }
                        }

                        $data['isp'] = $content['isp'];
                    }
                }
            }

            if($data) {
                $ip2location->country = @$data['country'];
                $ip2location->country_id = @$data['country_id'];
                $ip2location->area = @$data['area'];
                $ip2location->area_id = @$data['area_id'];
                $ip2location->region = @$data['region'];
                $ip2location->region_id = @$data['region_id'];
                $ip2location->city = @$data['city'];
                $ip2location->city_id = @$data['city_id'];
                $ip2location->county = @$data['county'];
                $ip2location->county_id = @$data['county_id'];
                $ip2location->isp = @$data['isp'];
                $ip2location->isp_id = @$data['isp_id'];
                $ip2location->updated_ts = time();
                $ip2location->save();
            }
        });
    }
}