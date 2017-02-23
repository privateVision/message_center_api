<?php
return [
    'yunpian' => [
        'apikey' => '0dbc5a50c034a8396b50f3a80609497d',
    ],

    'nowpay' => [
        'wechat' => [
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

        'alipay' => [
            'AppID' => "2088411293741002",
            'PriKey' => __DIR__ . '/nowpay_alipay_prikey.pem',
            'PubKey' => __DIR__ . '/nowpay_alipay_pubkey.pem',
        ],
    ],
];