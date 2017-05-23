<?php
namespace App\Jobs;
use App\Model\SMSRecord;
use App\Redis;

class SendSMS extends Job
{
    protected $smsconfig;
    protected $template_id;
    protected $mobile;
    protected $content;
    protected $code;

    public function __construct($smsconfig, $template_id, $mobile, $content, $code = 0)
    {
        $this->smsconfig = $smsconfig;
        $this->template_id = $template_id;
        $this->mobile = $mobile;
        $this->content = $content;
        $this->code = $code;
    }

    public function handle()
    {
        if(env('APP_DEBUG', true)) {
            $SMSRecord = new SMSRecord;
            $SMSRecord->mobile = $this->mobile;
            $SMSRecord->content = $this->content;
            $SMSRecord->code = $this->code;
            $SMSRecord->date = date('Ymd');
            $SMSRecord->hour = date('G');
            $SMSRecord->save();

            if($this->code) {
                Redis::set(sprintf('sms_%s_%s', $this->mobile, $this->code), 1, 'EX', 900);
            }

            return ;
        }

        if($this->attempts() >= 10) return;

        $data = [
            'apikey' => $this->smsconfig['apikey'],
            'mobile' => $this->mobile,
            'text' => $this->content,
        ];

        $restext = http_request($this->smsconfig['sender'], $data);

        log_debug('sendsms', ['req' => $data, 'res' => $restext]);

        if(!$restext) {
            return $this->release(5);
        }

        $res = json_decode($restext, true);
        if(!$res) {
            return $this->release(5);
        }

        if(@$res['code'] == 0) {
            $SMSRecord = new SMSRecord;
            $SMSRecord->mobile = $this->mobile;
            $SMSRecord->content = $this->content;
            $SMSRecord->code = $this->code;
            $SMSRecord->date = date('Ymd');
            $SMSRecord->hour = date('G');
            $SMSRecord->save();

            if($this->code) {
                // 60s内只能发送同模板短信1条
                Redis::set(sprintf('sms_%s_%s_60s', $this->mobile, $this->template_id), 1, 'EX', 60);
                // 24小时相同内容最多5次
                Redis::INCR(sprintf('sms_%s_%s_60s', $this->mobile, md5_36($this->content)));
                Redis::expire(sprintf('sms_%s_%s_60s', $this->mobile, md5_36($this->content)), 86400);
                // 24小时内只能发送10条
                // 把短信验证码存在redis，有效期900秒
                Redis::set(sprintf('sms_%s_%s', $this->mobile, $this->code), 1, 'EX', 900);

                $rediskey = sprintf('sms_%s_hourlimit', $this->mobile);
                if(Redis::exists($rediskey)) {
                    Redis::incr($rediskey);
                    Redis::expire($rediskey, 3600);
                } else {
                    Redis::incr($rediskey);
                }
                
            }
        } elseif(@$res['code'] != 8 && @$res['code'] != 17 && @$res['code'] != 22) {
            // 8:  同一个手机号 13065549260 30秒内重复提交相同的内容
            // 17: 24小时内同一手机号发送次数不能超过5次（相同内容）
            // 22: 验证码类短信1小时内同一手机号发送次数不能超过3次
            log_error('sendsms', ['req' => $data, 'res' => $restext]);
            return $this->release(5);
        }
    }
}