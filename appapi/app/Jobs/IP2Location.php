<?php

namespace App\Jobs;

use App\Model\Area;
use App\Redis;

class IP2Location extends Job
{
    protected $ip;

    public function __construct($ip) {
        $this->ip = $ip;
    }

    public function handle() {
        $ip = $this->ip;

        Redis::mutex_lock('ip2location_' . $ip, function() use($ip) {
            $ip2location = \App\Model\IP2Location::find($ip);
            if($ip2location && (time() - $ip2location->updated_ts) < 432000) { // 5天一更新
                return;
            }

            $data = null;
            
            // 淘宝API
            
            try {
                $content = http_curl('http://ip.taobao.com/service/getIpInfo.php', ['ip' => $ip], false, [
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ], 'json');
                
                if ($content['code'] == 0) {
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
            } catch(\Exception $e) {
                if($this->attempts() < 3) {
                    return $this->release(3);
                }
            }

            // 新浪API
            
            if (!$data) {
                try {
                    $content = http_curl('http://int.dpool.sina.com.cn/iplookup/iplookup.php', ['format' => 'json', 'ip' => $ip], false, [
                        CURLOPT_TIMEOUT => 3,
                        CURLOPT_CONNECTTIMEOUT => 3,
                    ], 'json');
                    
                    if ($content['ret'] == 1) {
                        $data['country'] = $content['country'];
                        $data['country_id'] = $content['country'] == '中国' ? 'CN' : '';

                        if ($content['province']) {
                            $area = Area::where('name', 'like', "%{$content['province']}%")->first();
                            if ($area) {
                                $data['region'] = $area->name;
                                $data['region_id'] = $area->id;
                            }
                        }

                        if ($content['city']) {
                            $area = Area::where('name', 'like', "%{$content['city']}%")->first();
                            if ($area) {
                                $data['city'] = $area->name;
                                $data['city_id'] = $area->id;
                            }
                        }

                        $data['isp'] = $content['isp'];
                    }
                } catch(\Exception $e) {
                    if($this->attempts() < 3) {
                        return $this->release(3);
                    }
                }
            }

            if ($data) {
                if(!$ip2location) {
                    $ip2location = new \App\Model\IP2Location;
                    $ip2location->ip = $ip;
                }
                
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