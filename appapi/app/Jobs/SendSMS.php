<?php
namespace App\Jobs;
use App\Exceptions\Exception;
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

    public static function verify($mobile, $template_id, $content, $is_sendcode) {
        if(!env('APP_DEBUG')) {
            // 规则4：同一个手机号相同内容，24小时内最多能获取到5条
            if (Redis::GET(sprintf('sms_%s_24hc_%s', $mobile, md5_36($content))) >= 5) {
<<<<<<< HEAD
                throw new Exception('短信发送过于频繁，请明天再试');
=======
                throw new Exception(trans('messages.sms_text_same_limit'));
>>>>>>> dev
            }

            if($is_sendcode) {
                // 规则1：同一个手机号同一个验证码模板，每30秒只能获取1条
                if (Redis::EXISTS(sprintf('sms_%s_60st_%s', $mobile, $template_id))) {
<<<<<<< HEAD
                    throw new Exception('短信发送过于频繁');
=======
                    throw new Exception(trans('messages.sms_60s_limit'));
>>>>>>> dev
                }

                // 规则2：同一个手机号验证码类内容，每小时最多能获取3条
                if (Redis::GET(sprintf('sms_%s_1ht', $mobile)) >= 3) {
<<<<<<< HEAD
                    throw new Exception('短信发送次数超过限制，请稍候再试');
=======
                    throw new Exception(trans('messages.sms_1h_limit'));
>>>>>>> dev
                }

                //规则3：同一个手机号验证码类内容，24小时内最多能获取到5条
                if (Redis::GET(sprintf('sms_%s_24ht', $mobile)) >= 5) {
<<<<<<< HEAD
                    throw new Exception('短信发送次数超过限制，请明天再试');
=======
                    throw new Exception(trans('messages.sms_24h_limit'));
>>>>>>> dev
                }
            }
        }
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

        if($this->attempts() >= 3) return;

        try {
            static::verify($this->mobile, $this->template_id, $this->content, $this->code != '');
        } catch(Exception $e) {
            log_debug('sendsms', ['mobile' => $this->mobile, 'content' => $this->content], $e->getMessage());
            return;
        }

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

            // 24小时相同内容
            $rediskey = sprintf('sms_%s_24hc_%s', $this->mobile, md5_36($this->content));
            if(!Redis::EXISTS($rediskey)) {
                Redis::SET($rediskey, 1, 'EX', 86400);
            } else {
                Redis::INCR($rediskey);
            }

            if($this->code) {
                // 60s内只能发送同模板短信1条
                Redis::SET(sprintf('sms_%s_60st_%s', $this->mobile, $this->template_id), 1, 'EX', 60);

                // 每小时短信条数
                $rediskey = sprintf('sms_%s_1ht', $this->mobile);
                if(!Redis::EXISTS($rediskey)) {
                    Redis::SET($rediskey, 1, 'EX', 3600);
                } else {
                    Redis::INCR($rediskey);
                }

                // 24小时短信条数
                $rediskey = sprintf('sms_%s_24ht', $this->mobile);
                if(!Redis::EXISTS($rediskey)) {
                    Redis::SET($rediskey, 1, 'EX', 86400);
                } else {
                    Redis::INCR($rediskey);
                }

                // 短信验证码有效期900秒
                Redis::SET(sprintf('sms_%s_%s', $this->mobile, $this->code), 1, 'EX', 900);
            }
        } elseif(@$res['code'] != 8 && @$res['code'] != 17 && @$res['code'] != 22) {
            // 8:  同一个手机号30秒内重复提交相同的内容
            // 17: 规则3：同一个手机号验证码类内容，24小时内最多能获取到5条
            // 22: 规则2：同一个手机号验证码类内容，每小时最多能获取3条
            log_error('sendsms', ['req' => $data, 'res' => $restext]);
        } else {
            return $this->release(5);
        }
    }
}