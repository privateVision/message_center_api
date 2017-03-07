<?php
// 尽量不要使用{0},{1}这样的变量替换符，这个文件是要给小白配置的，不懂的小白把{0},{1}顺序调换，程序就出错了
// 例：
// trans('我叫{0}，今年{1}岁'， 'lixx', 28)           ---> 我叫lixx，今年28岁了
// trans('他今年{0}岁，他的名字叫{1}'， 'lixx', 28)   ---> 他今年lixx岁，他的名字叫28   --> 这个时候必须要改代码才能得到正确的
// 
// 推荐这种格式（以不变应万变）：trans('我叫:name，今年:age', ['name' => 'lixx', 'age' => 28])

return [
    'phone_unbind_code'             => "【安峰网】您好，您的验证码是",
    'unbind_newname '               => "【安锋网】您好，你解绑后，为君生成新名",
    "bind error"                    => "【安锋网】您好，绑定错误！" ,
    "mobile_bind_success"           => "【安锋网】手机绑定成功！" ,
    "mobile_bind_faild"             => "【安锋网】手机绑定失败！" ,
    "please_bind_mobile"            => "【安锋网】请绑定手机号！",
    "auth_code_error"               => "【安锋网】验证码错误！" ,
    "user_freeze"                   => "【安锋网】您好，账号已冻结！",
    "user_freeze_faild"             => " 您好，账号冻结失败！",
    "user_message_notfound"         =>  "您好账户信息未找到！",
    "unfreeze_success"              => "【安锋网】账号成功冻结！",
    "unfreeze_faild"                => "【安锋网】你好账号解冻失败！",
    "name_type_error"               => "【安锋网】您好，名字的格式不正确！",
    "fpay0"                         => "【安锋网】您好，F币支付成功！",
    "fpay1"                         => "【安锋网】您好，F币支付失败！",
    "nomoney"                       => "【安锋网】您好金额不足",
    "error_user_message"            => "【安锋网】请填写正确的用户信息",
    "sms_code"                      => "【安锋网】您好，您的验证码是:{0}",
    "sms_code_error"                => "【安锋网】您好，你的短信验证码错误！",
    "sms_code_success"              => "【安锋网】您好短信，验证码成功！",
    "money_format_error"            => "您好金额的格式不正确！"          ,
    "password_type_error"           => "您好，密码格式不正确！",
    "mobile_type_error"             => "请填写正确的，手机号格式",
    'phone_register'                => "【安锋网】恭喜您注册成功，用户名：:username  密码：:password", // 手机一键登陆成功后给发送用户帐户信息
    "sms_limit_code"                => "您好，每天最多发送三次!"
];
