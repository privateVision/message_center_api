<?php
return [
    'product_default_name'                  => '道具', // 比如：购买道具失败
    'freeze_onlogin'                        => '账号已被冻结，无法登录',
    'role_not_exists'                       => '角色不存在，无法登录',
    'role_freeze_onlogin'                   => '角色已被冻结，无法登录',
    '3th_not_register'                      => '未注册第三方账号，请注册',
    'freeze'                                => '账号被冻结',
    'invalid_token'                         => '会话失效，请重新登录',
    'invalid_smscode'                       => '验证码不正确，或已过期',
    'unionid_empty'                         => 'unionid不允许为空',
    '3th_unknow'                            => '未知的第三方登录类型，:type',
    'not_register'                          => '尚未注册',
    'not_accept_sms'                        => '服务器等待收到短信...',
    'login_error'                           => '错误次数太多，请稍后再试',
    'login_fail'                            => '用户名或者密码不正确',
    'already_register'                      => '用户已注册，请直接登录',
    'mobile_not_bind'                       => '手机号码尚未绑定',
    'order_not_exists'                      => '订单不存在',
    'invalid_appid'                         => '_appid" not exists: :appid',
    'sign_error'                            => '签名验证失败',
    'order_not_use_f'                       => '不能使用余额或优惠券直接抵扣',
    'order_already_success'                 => '订单已支付完成，请勿重复支付',
    'coupon_expire'                         => '优惠券不可使用，已过期',
    'unionpay_fail'                         => '银联支付请求失败',
    'unionpay_fail_1'                       => '银联支付请求失败 :respMsg',
    'pay_fail'                              => '发起支付失败',
    'pay_fail_1'                            => '发起支付失败（:return_msg）',
    'check_in_before_pay'                   => '帐号未实名制，无法支付，请先实名后再操作',
    'product_not_exists'                    => '未找到相关的商品',
    'notifyurl_error'                       => '请填写正确的通知地址',
    'bundle_ipa_not_exists'                 => 'bundle_id 或iap不存在',
    'mycard_request_fail'                   => 'MyCard 支付请求失败',
    'reset_password_invalid_page'           => '修改失败，页面已失效',
    'user_not_exists'                       => '用户不存在',
    'nickname_not_exists'                   => '修改失败，昵称已经存在',
    'modify_usersub_not_exists'             => '修改失败，小号不存在',
    'usersub_much'                          => '小号创建数量已达上限',
    'birthday_format_error'                 => '生日格式不正确，yyyymmdd',
    'oldpassword_error'                     => '旧的密码不正确',
    'mobile_bind_other'                     => '手机号码已经绑定了其它账号',
    'mobile_already_bind'                   => '该账号已经绑定了这个手机号码',
    'func_disable'                          => '该功能已停用',
    'user_already_bind_mobile'              => '该账号已经绑定了手机号码',
    'not_bind_onunbind'                     => '还未绑定手机号码，无法解绑',
    'reset_username_onunbind'               => '您必需重设您的用户名才能解绑',
    'username_exists_onbind'                => '解绑失败，用户名已被占用',
    'not_reset_password_unbind_mobile'      => '还未绑定手机号码，无法使用该方式重置密码',
    'cardno_error'                          => '身份证号码不正确',
    '3th_already_bind'                      => '账号已经绑定了:type',
    '3th_already_bind_other'                => ':type已经绑定了其它账号',
    '3th_unbind_error'                      => '为了防止遗忘账号，请绑定手机或者其他社交账号后再解除绑定',
    'avatar_set_error'                      => '头像上传失败：:eMsg',
    'username_exists_onset'                 => '设置失败，用户名已被占用',
    'param_format_error'                    => '参数":key"格式不正确',
    'param_missing'                         => 'param is missing: :key',
    'mobile_format_error'                   => ':mobile不是一个有效的手机号码',
    'useranme_format_error_d'               => '用户名错误，不能为纯数字',
    'useranme_format_error_l'               => '用户名长度在6-15位之间',
    'useranme_format_error_dw'              => '用户名只能由数字和字母组成',
    'url_format_error'                      => ':url url错误',
    'password_empty'                        => '密码不能为空',
    'nickname_format_error_l'               => '昵称长度不能超过14个字符，1个汉字算2个',
    'subnickname_format_error_l'            => '昵称长度不能超过10个字符，1个汉字算2个',
    "app_buy_faild"                         => "您好，充值失败！",
    "apple_buy_success"                     => "苹果购买成功！",
    "app_param_type_error"                  => "您好，传递的参数的格式错误！",
    "request_time_out"                      => "您好，请求超时，请重试！",
    "apple_rer_error_type"                  => "苹果返回的数据有误，请重新尝试！",
    'mycard_callback_success'               => '购买“:name”成功',
    'mycard_callback_fail'                  => '购买“:name”失败，:ReturnMsg',
    'not_set_notify_url'                    => '未设置订单成功通知地址',
    'not_payconfig'                         => '未获取到支付配置', // error
    'not_allow_pay_type'                    => '不支持该支付场景', // error
    'app_package_not_set'                   => 'procedures_extend.package_name 未配置', // error
<<<<<<< HEAD
    'ipfreeze'                              => '设备被封',
    'order_verify_fail'                     => '订单验证失败', // error
    'order_handle_fail'                     => '订单处理失败', // error
    'order_status_error'                    => '订单状态不正确，或已完成', // 在尝试处理订单时，发现订单已经被处理过
=======
    'http_request_error'                    => '连接第三方服务器失败',
>>>>>>> e74101cd396da95a566361d237cc7120158af8c4
];
/*
return [
    'phone_unbind_code'             => "【安峰网】您好，您的验证码是",
    'unbind_newname '               => "【安锋网】您好，你解绑后，为君生成新名",
    "bind error"                    => "【安锋网】您好，绑定错误！" ,
    "mobile_bind_success"           => "【安锋网】手机绑定成功！" ,
    "mobile_bind_faild"             => "【安锋网】手机绑定失败！" ,
    "please_bind_mobile"            => "该安锋平台账号尚未绑定手机，为了账号安全所出售账号必须绑定手机号，请您在安锋助手或安锋官网（www.anfeng.cn）绑定手机号。",
    "auth_code_error"               => "【安锋网】验证码错误！" ,
    "user_freeze"                   => "【安锋网】您好，账号已冻结！",
    "user_freeze_faild"             => " 您好，账号冻结失败！",
    "user_message_notfound"         =>  "您好账户信息未找到！",
    "unfreeze_success"              => "账号成功解冻！",
    "unfreeze_faild"                => "【安锋网】你好账号解冻失败！",
    "name_type_error"               => "【安锋网】您好，名字的格式不正确！",
    "fpay0"                         => "【安锋网】您好，F币支付成功！",
    "fpay1"                         => "【安锋网】您好，F币支付失败！",
    "nomoney"                       => "对不起,您的安锋账号充值金额不足",
    "error_user_message"            => "您输入的账号或密码不正确，请重新输入。",
    "sms_code"                      => "【安锋网】您好，您的验证码是:{0}",
    "sms_code_error"                => "【安锋网】您好，你的短信验证码错误！",
    "sms_code_success"              => "【安锋网】您好短信，验证码成功！",
    "money_format_error"            => "您好金额的格式不正确！"          ,
    "password_type_error"           => "您好，密码格式不正确！",
    "mobile_type_error"             => "请填写正确的，手机号格式",
    'phone_register'                => "【安锋网】恭喜您注册成功，用户名：:username  密码：:password", // 手机一键登陆成功后给发送用户帐户信息
    "sms_limit_code"                => "您好，每天最多发送三次!",
    "app_buy_faild"                 => "您好，充值失败！",
    "request_time_out"              => "您好，请求超时，请重试！",
    "apple_rer_error_type"          => "苹果返回的数据有误，请重新尝试！",
    "apple_buy_success"             => "苹果购买成功！",
    "app_param_type_error"          => "您好，传递的参数的格式错误！",
    //loginAction
    "order_info_error"              => "订单信息有误",
    "param_type_error"              => "参数格式不正确",
    "product_not_found"             => "您好产品信息未找到" ,

];
*/