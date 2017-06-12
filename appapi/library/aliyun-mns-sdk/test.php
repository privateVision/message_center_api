<?php
require_once(__DIR__ . '/mns-autoloader.php');
use AliyunMNS\Client;
use AliyunMNS\Topic;
use AliyunMNS\Constants;
use AliyunMNS\Model\MailAttributes;
use AliyunMNS\Model\SmsAttributes;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\PublishMessageRequest;

class PublishBatchSMSMessageDemo
{
    public function run()
    {
        // 公网Endpoint ：http(s)://1530228896015232.mns.cn-hangzhou.aliyuncs.com/
        // 私网Endpoint ：http://1530228896015232.mns.cn-hangzhou-internal.aliyuncs.com/
        // VPCEndpoint ：http://1530228896015232.mns.cn-hangzhou-internal-vpc.aliyuncs.com
        
        /**
         * Step 1. 初始化Client
         */
        $this->endPoint = "https://1530228896015232.mns.cn-hangzhou.aliyuncs.com/";
        $this->accessId = "LTAI3zlHhNCITkV5";
        $this->accessKey = "YUTzHwDsHO9jjjeE3BcNwyKj0RURt8";
        $this->client = new Client($this->endPoint, $this->accessId, $this->accessKey);
        /**
         * Step 2. 获取主题引用
         */
        $topicName = "sms.topic-cn-hangzhou";
        $topic = $this->client->getTopicRef($topicName);
        /**
         * Step 3. 生成SMS消息属性
         */
        // 3.1 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
        $batchSmsAttributes = new BatchSmsAttributes("安锋游戏", "SMS_67171204");
        // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
        $batchSmsAttributes->addReceiver("15021829660", array("code" => "123456"));
        //$batchSmsAttributes->addReceiver("YourReceiverPhoneNumber2", array("YourSMSTemplateParamKey1" => "value1"));
        $messageAttributes = new MessageAttributes(array($batchSmsAttributes));
        /**
         * Step 4. 设置SMS消息体（必须）
         *
         * 注：目前暂时不支持消息内容为空，需要指定消息内容，不为空即可。
         */
         $messageBody = "smsmessage";
        /**
         * Step 5. 发布SMS消息
         */
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        try
        {
            $res = $topic->publishMessage($request);
            echo $res->isSucceed();
            echo "\n";
            echo $res->getMessageId();
            echo "\n";
        }
        catch (MnsException $e)
        {
            echo $e;
            echo "\n";
        }
    }
}
$instance = new PublishBatchSMSMessageDemo();
$instance->run();