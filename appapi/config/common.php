<?php
return [
    'storage_cdn' => [
        // doc: http://developer.qiniu.com/kodo/sdk/1241/php
        'qiniu' => [
            'access_key' => 'VN1T4HyOswCiFxhsg92BrHU9_oCxmVfvz8PWPW8l',
            'secret_key' => 'LUjILsCuVLX99qMgI8fpPFKIGNgceWMioUfS1_nQ',
            'base_url' => 'http://avatar.anfeng.com/',
            'bucket' => 'anfeng-avatar',
        ]
    ],

    'oauth' => [
        'weixin' => ['text' => '微信'],
        'qq' => ['text' => 'QQ'],
        'weibo' => ['text' => '微博'],
        'baidu' => ['text' => '百度'],
        'facebook' => ['text' => 'Facebook'],
        'yyb_qq' => ['text' => '应用宝-QQ'],
        'yyb_weixin' => ['text' => '应用宝-微信'],
        'yyb_guest' => ['text' => '应用宝-游客'],
    ],

    'smsconfig' => [
        'receiver' => '10690735126170',
        'apikey' => '560ff300cabf7b7df7e3c02f892bfd43',
        'sender' => 'http://yunpian.com/v1/sms/send.json',
        'hour_limit' => 3, // 每小时短信发送次数限制
        'template' => [
            //  手机注册成功    一键登录    主动短信验证码登录
            'mobile_register'  =>  '【安锋游戏】恭喜您注册成功，用户名：#username#    密码：#password#，您也可以使用手机号码作为账号登录。',
            //  重置密码时发送手机验证码
            'reset_password'  =>  '【安锋游戏】验证码为：#code#，您正在使用该手机号码重置登录密码，请勿向任何人泄露您的验证码。',
            //  平台登录时绑定手机发送短信验证码
            'oauth_login_bind'  =>  '【安锋游戏】验证码为：#code#，您正在通过该手机号码绑定安锋账号，请勿向任何人泄露您的验证码。',
            //  平台注册成功后向用户发送账号密码
            'oauth_register'  =>  '【安锋游戏】恭喜您注册成功，您可以使用#type#直接登录，也可以使用手机号码或用户名登录，用户名：#username#    密码：#password#',
            //  在用户中心绑定手机
            'bind_phone'  =>  '【安锋游戏】验证码为：#code#，您正在通过手机号码绑定安锋账号，请勿向任何人泄露您的验证码。',
            //  解除绑定手机号码
            'unbind_phone'  =>  '【安锋游戏】验证码为：#code#，您正在通过该手机号码解绑安锋账号，请勿向任何人泄露您的验证码。',
            //  手机号码+验证码登录
            'login_phone'  =>  '【安锋游戏】验证码为：#code#，您正在使用该手机号码登录安锋游戏，请勿向任何人泄露您的验证码。',
        ]
    ],

    'pay_methods' => [
        2 => ['type' => 'wechat', 'api' => '/api/pay/wechat/request'],
        4 => ['type' => 'alipay', 'api' => '/api/pay/alipay/request'],
        8 => ['type' => 'unionpay', 'api' => '/api/pay/unionpay/request'],
        16 => ['type' => 'mycard', 'api' => '/api/pay/mycard/request'],
        32 => ['type' => 'nowpay_wechat', 'api' => '/api/pay/nowpay_wechat/request'],
    ],

    'payconfig' => [
        'alipay' => [
            'AppID' => "2088411293741002",
            'PriKey' => __DIR__ . '/alipay_prikey.pem', 
            'PubKey' => __DIR__ . '/alipay_pubkey.pem',
        ],

        'unionpay' => [
            'merid' => '898110257340281',
            'pfx' => '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',
            'pfx_pwd' => '906536',
            'verify' =>  __DIR__ . '/unionpay.cert',
            // 手机APP交易请求地址
            'trade_url' => 'https://gateway.95516.com/gateway/api/appTransReq.do',
            //单笔查询请求地址
            'query_url' => 'https://gateway.95516.com/gateway/api/queryTrans.do',
            /*
                        卡号      6216261000000000018
                        卡性质    借记卡
                        机构名称  平安银行
                        手机号码  13552535506
                        密码      123456
                        CVN2
                        有效期
                        证件号    341126197709218366
                        姓名      全渠道
                        ----------------------------
                        卡号      6221558812340000
                        卡性质    贷记卡
                        机构名称  平安银行
                        手机号码  13552535506
                        密码      123456
                        CVN2      123
                        有效期    1711
                        证件号    341126197709218366
                        姓名      互联网
            */
        ],

        'nowpay_wechat' => [
            'appId' => '1440581085864755',
            'secure_key' => 'vm4ZhxWPzagx3BH9KnHIcmUGyVngEJtD',
            'mhtCharset' => 'UTF-8',
            'mhtCurrencyType' => '156',
            'dtFormat' => 'YmdHis',
            'mhtOrderTimeOut' => '3600',
            'mhtOrderType' => '01',
            'payChannelType' => '13',
            'mhtSignType' => 'MD5',
        ],

        'wechat' => [
            'appid' => 'wx873090ec90259693',
            'mch_id' => '1265654801',
            'key' => 'cf281b632d1671ed2a94d8f7cdfe2ff0',
            'pemfile_key' => __DIR__ . '/wechat_key.pem',
            'pemfile_cert' => __DIR__ . '/wechat_cert.pem',
        ],
        
        'mycard' => [
            'FacServiceId' => 'NOVAS',
            'FacServerKey' => '83BC614DF932329D52B9FC73F7BA7DEB',
            'autocode_url' => env('APP_DEBUG') ? 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal' : 'https://b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal',
            'webpay_url' => env('APP_DEBUG') ? 'https://test.mycard520.com.tw/MyCardPay/' : 'https://www.mycard520.com.tw/MyCardPay/',
            'pay_result_url' => env('APP_DEBUG') ? 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery' : 'https://b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery',
        ],
    ],
];