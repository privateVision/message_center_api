<?php
return [
    'oauth' => [
        'weixin' => ['text' => '微信'],
        'qq' => ['text' => 'QQ'],
        'weibo' => ['text' => '微博'],
        'baidu' => ['text' => '百度'],
        'facebook' => ['text' => 'Facebook'],
        'yyb_qq' => ['text' => '应用宝-QQ'],
        'yyb_weixin' => ['text' => '应用宝-微信'],
        'yyb_guest' => ['text' => '应用宝-游客'],
        'uc' => ['text' => 'UC'],
        'lenovo' => ['text' => '联想'],
        'xiaomi' => ['text' => '小米'],
        'hauwei' => ['text' => '华为'],
        'amigo' => ['text' => '金立'],
        'oppo' => ['text' => 'oppo'],
        'vivo' => ['text' => 'vivo'],
        'leshi' => ['text' => '乐视'],
        'baidu' => ['text' => '百度'],
    ],
    
    'pay_methods' => [
        // pay_type，该支付方式支持的支付场景，0sdk,1url_scheme,2web
        0 => ['type' => 'wechat', 'api' => 'api/pay/wechat/request', 'pay_type' => [0,1]],
        1 => ['type' => 'alipay', 'api' => 'api/pay/alipay/request', 'pay_type' => [0,1]],
        2 => ['type' => 'unionpay', 'api' => 'api/pay/unionpay/request', 'pay_type' => [0]],
        3 => ['type' => 'mycard', 'api' => 'api/pay/mycard/request', 'pay_type' => [2]],
        4 => ['type' => 'nowpay_wechat', 'api' => 'api/pay/nowpay_wechat/request', 'pay_type' => [0]],
        5 => ['type' => 'paypal', 'api' => 'api/pay/paypal/request', 'pay_type' => [2]],
    ],
];