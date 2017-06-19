<?php
return [
    'login_check_abnormal' => true, // 在登陆时检查登陆是否异常（长时间内未登陆，再次登陆就算异常）

    'hotupdate' => [
        [
            'pid' => [], // 哪些pid应用该更新，必须有一个pid是空的，用来作默认值
            'manifest' => [
                'version' => '1.0.0',
                'bundles' => [
                    ['type' => 'lib', 'pkg' => 'com.anfeng.pay']
                ],
            ],
            'updateinfo' => [
                'pkg' => 'com.anfeng.pay',
                'version' => 400,
                'use_version' => 400,
                'url' => 'http://afsdkup.qcwan.com/down/com.anfeng.pay.apk',
            ],
        ],
    ],

    'basic' => [
        'default_avatar' => 'http://avatar.anfeng.com/avatar_default.png', // 默认头像
        'service_share' => 'http://www.anfeng.cn/app', // 分享页面
        'service_page' => 'http://m.anfeng.cn/service.html', // 客服页面
        'service_phone' => '4000274365', // 客服电话
        'service_qq' => '4000274365', // 客服QQ
        'af_download' => 'http://appicdn.anfeng.cn/down/AnFengHelper_lastest.apk', // 安锋助手下载地址
        'default_heartbeat_interval' => '2000', //默认心跳时长，毫秒
        'logout_img' => 'http://appicdn.anfeng.cn/app/upload/appforsdk.jpg', // 客户端“退出”时，展示图片
        'logout_redirect' => 'http://www.anfeng.cn/', // 客户端“退出”时，点击图片要跳转的地址
        'protocol_title' => '安锋用户协议', // 用户协议标题
        'protocol_url' => 'http://passtest.anfeng.cn/agreement.html', // 用户协议
        'oauth_url_qq' => 'http://passtest.anfeng.cn/oauth/login/qq', // QQ登陆地址
        'oauth_url_weixin' => 'http://passtest.anfeng.cn/oauth/login/weixin', // 微信登陆地址
        'oauth_url_weibo' => 'http://passtest.anfeng.cn/oauth/login/weibo', // 微博登陆地址
        'reset_password_url' => 'http://passtest.anfeng.cn/reset_password.html', // 用户重设密码接口地址
    ],

    'storage_cdn' => [
        // doc: http://developer.qiniu.com/kodo/sdk/1241/php
        'qiniu' => [
            'access_key' => 'VN1T4HyOswCiFxhsg92BrHU9_oCxmVfvz8PWPW8l',
            'secret_key' => 'LUjILsCuVLX99qMgI8fpPFKIGNgceWMioUfS1_nQ',
            'base_url' => 'http://avatar.anfeng.com/',
            'bucket' => 'anfeng-avatar',
        ]
    ],

    'smsconfig' => [
        'receiver' => '10690735126170',
        'apikey' => '560ff300cabf7b7df7e3c02f892bfd43',
        'sender' => 'http://yunpian.com/v1/sms/send.json',

        // 注，验证码类短信必需包含“#code#”
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
            // mycard有多帐号机制，通常在回调里会传FacServiceId，根据FacServiceId调用不同的FacServerKey，验证不同的回调，目前我们这边写死了，默认就这一个账号
            'FacServiceId' => 'NOVAS',
            'FacServerKey' => '83BC614DF932329D52B9FC73F7BA7DEB',
            'AuthGlobal' => env('APP_DEBUG') ? 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal' : 'https://b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal',
            'MyCardPay' => env('APP_DEBUG') ? 'https://test.mycard520.com.tw/MyCardPay/' : 'https://www.mycard520.com.tw/MyCardPay/',
            'TradeQuery' => env('APP_DEBUG') ? 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery' : 'https://b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery',
            'TradeQueryHost' => env('APP_DEBUG') ? '218.32.37.148' : '220.130.127.125',
            'PaymentConfirm' => env('APP_DEBUG') ? 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm' : 'https://b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm',
        ],

        //https://console.developers.google.com/iam-admin/serviceaccounts/project?project=api-project-70324813277
        'googleplay'=>[
            'cert' => __DIR__ . '/google_play.json'  //通过google play控制台生产秘钥账号
        ],

        'IOS' => [
            'verify_receipt_sandbox' => 'https://sandbox.itunes.apple.com/verifyReceipt',
            'verify_receipt' => 'https://buy.itunes.apple.com/verifyReceipt',
        ],

        'paypal' => [
            // 默认支付帐户
            'account_1' => [
                'ClientID' => env('APP_DEBUG', true) ? 'AVKsKYJ2c3PWKyeSIfbrHw5SRJPSs_4uq9FuuAyXhYdcwoDnbMZ3a_XdusnOn1LxxWgTCQPhUqOCC7zC' : 'AbY9SzC7g_xppRt1xAWQljHbz3FBQU7XXOGGTpKBP6quZZnvFXdzguCX3ZBqcNOmAaXuEwrQ1C0eaQns',
                'Secret' => env('APP_DEBUG', true) ? 'EAPw8swZBsOtySBYaXYzKRyPRIIWY_-aEbzTGMSoXSdsfSO44E48h3qFxez6GWWHBdRNZYG7kVabtVNX' : 'ECduHwsLdmLpldfrpESOjmKkyIN7Buqg6Tww1NXtQ4V2GMH6VO6B9a11-jdoGlW4eypIhPaoTpioYDqv',
                'business' => env('APP_DEBUG', true) ? 'sss60m@qq.com' : 'guxuan@novasmobi.com', // 收款帐号，付款帐号：sss60b@qq.com 12345678
            ],

            // 支付金额大于12USD使用帐户2
            'account_2' => [
                'ClientID' => env('APP_DEBUG', true) ? 'AVKsKYJ2c3PWKyeSIfbrHw5SRJPSs_4uq9FuuAyXhYdcwoDnbMZ3a_XdusnOn1LxxWgTCQPhUqOCC7zC' : 'AbY9SzC7g_xppRt1xAWQljHbz3FBQU7XXOGGTpKBP6quZZnvFXdzguCX3ZBqcNOmAaXuEwrQ1C0eaQns',
                'Secret' => env('APP_DEBUG', true) ? 'EAPw8swZBsOtySBYaXYzKRyPRIIWY_-aEbzTGMSoXSdsfSO44E48h3qFxez6GWWHBdRNZYG7kVabtVNX' : 'ECduHwsLdmLpldfrpESOjmKkyIN7Buqg6Tww1NXtQ4V2GMH6VO6B9a11-jdoGlW4eypIhPaoTpioYDqv',
                'business' => env('APP_DEBUG', true) ? 'sss60m@qq.com' : 'guxuan@novasmobi.com', // 收款帐号，付款帐号：sss60b@qq.com 12345678
            ],

            'access_token_url' => env('APP_DEBUG', true) ? 'https://api.sandbox.paypal.com/v1/oauth2/token' : 'https://api.paypal.com/v1/oauth2/token',
            'payment_url' => env('APP_DEBUG', true) ? 'https://api.sandbox.paypal.com/v1/payments/payment' : 'https://api.paypal.com/v1/payments/payment',
        ]
    ],
];