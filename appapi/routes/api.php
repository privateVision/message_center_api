<?php
Route::group(['prefix' => 'web'], function () {
    Route::any('mycard/rescue', 'Web\\MycardController@RescueAction'); // mycard订单补储
    Route::any('mycard/query', 'Web\\MycardController@QueryAction');   // mycard订单查询
});

// 支付回调
Route::group(['prefix' => 'pay_callback'], function () {
    Route::any('nowpay_wechat', 'PayCallback\\NowpayWechatController@CallbackAction');                      // 现代支付，微信支付回调
    Route::any('alipay', 'PayCallback\\AlipayController@CallbackAction');                                   // 支付宝支付回调
    Route::any('unionpay', 'PayCallback\\UnionpayController@CallbackAction');                               // 银联支付回调
    Route::any('wechat', 'PayCallback\\WechatController@CallbackAction');                                   // 微信支付回调

    //渠道支付回调
    Route::any('baidu', 'PayCallback\\BaiduController@CallbackAction');
    Route::any('mycard', 'PayCallback\\MycardController@CallbackAction');                                   // mycard支付回调
    Route::any('uc', 'PayCallback\\UcController@CallbackAction');                                           // Uc支付回调
});
    
// 各种回调
Route::group(['prefix' => 'callback'], function () {
    Route::any('yunpian/request', 'Callback\\YunpianController@RequestAction');                             // 云片手机短信回调
});
    
// API接口
Route::group(['prefix' => 'api'], function () {
    Route::any('app/initialize', 'Api\\AppController@InitializeAction');                                    // 初始化
    Route::any('app/verify_sms', 'Api\\AppController@VerifySMSAction');                                     // 验证手机验证码是否正确
    Route::any('app/uuid', 'Api\\AppController@UuidAction');                                                // 获取一个UUID，用户无法获取设备UUID时
    Route::any('app/logout', 'Api\\AppController@LogoutAction');                                            // 退出客户端
    Route::any('app/hotupdate','Api\\AppController@HotupdateAction');                                       //获取热更新数据
    
    Route::any('account/token/login', 'Api\\Account\\TokenController@LoginAction');                         // 自动登录
    Route::any('account/login', 'Api\\Account\\UserController@LoginAction');                                // 用户名或手机号码登陆
    Route::any('account/register', 'Api\\Account\\UserController@RegisterAction');                          // 用户名注册
    Route::any('account/username', 'Api\\Account\\UserController@UsernameAction');                          // 生成随机用户名
    Route::any('account/onekey/sms_token', 'Api\\Account\\OnekeyController@SMSTokenAction');                // 手机号码一键登陆(获取发送短信的token)
    Route::any('account/onekey/login', 'Api\\Account\\OnekeyController@LoginAction');                       // 手机号码一键登陆
    Route::any('account/user/sms_reset_password', 'Api\\Account\\UserController@SMSResetPasswordAction');   // 发送重设密码的验证码
    Route::any('account/user/reset_password', 'Api\\Account\\UserController@ResetPasswordAction');          // 通过验证码重设密码
    Route::any('account/mobile/sms_login', 'Api\\Account\\MobileController@SMSLoginAction');                // 手机验证码登陆（发送短信）
    Route::any('account/mobile/login', 'Api\\Account\\MobileController@LoginAction');                       // 手机验证码登陆
    Route::any('account/guest/login', 'Api\\Account\\GuestController@LoginAction');                         // 游客登陆
    Route::any('account/oauth/register', 'Api\\Account\\OauthController@RegisterAction');                   // 平台注册
    Route::any('account/oauth/login', 'Api\\Account\\OauthController@LoginAction');                         // 平台登陆
    
    Route::any('user/recharge', 'Api\\UserController@RechargeAction');                                      // 充值记录（充F币）
    Route::any('user/consume', 'Api\\UserController@ConsumeAction');                                        // 消费记录
    Route::any('user/hide_order', 'Api\\UserController@HideOrderAction');                                   // 隐藏订单
    Route::any('user/balance', 'Api\\UserController@BalanceAction');                                        // 用户余额
    Route::any('user/sms_bind_phone', 'Api\\UserController@SMSBindPhoneAction');                            // 发送绑定手机的短信
    Route::any('user/bind_phone', 'Api\\UserController@BindPhoneAction');                                   // 绑定手机号码
    Route::any('user/sms_unbind_phone', 'Api\\UserController@SMSUnbindPhoneAction');                        // 发送解绑手机的短信
    Route::any('user/unbind_phone', 'Api\\UserController@UnbindPhoneAction');                               // 解绑手机号码
    Route::any('user/sms_phone_reset_password', 'Api\\UserController@SMSPhoneResetPasswordAction');         // 发送重置密码的短信
    Route::any('user/phone_reset_password', 'Api\\UserController@PhoneResetPasswordAction');                // 通过手机号码重置密码
    Route::any('user/by_oldpassword_reset', 'Api\\UserController@ByOldPasswordResetAction');                // 通过旧的密码重置密码
    Route::any('user/report_role', 'Api\\UserController@ReportRoleAction');                                 // 上报玩家角色信息
    Route::any('user/attest', 'Api\\UserController@AttestAction');                                          // 实名认证
    Route::any('user/info', 'Api\\UserController@InfoAction');                                              // 用户详细信息
    Route::any('user/bind_oauth', 'Api\\UserController@BindOauthAction');                                   // 第三方帐号绑定
    Route::any('user/unbind_oauth', 'Api\\UserController@UnbindOauthAction');                               // 第三方帐号解绑
    Route::any('user/event', 'Api\\UserController@EventAction');                                            // 触发用户事件
    Route::any('user/set_avatar', 'Api\\UserController@SetAvatarAction');                                   // 上传用户头像
    Route::any('user/set_username', 'Api\\UserController@SetUsernameAction');                               // 设置username
    Route::any('user/set_nickname', 'Api\\UserController@SetNicknameAction');                               // 设置nickname
    Route::any('user/bind_list', 'Api\\UserController@BindListAction');                                     // 获取用户绑定了哪些平台、邮箱、手机
    Route::any('user/set', 'Api\\UserController@SetAction');                                                // 设置用户资料
    
    Route::any('user_sub/list', 'Api\\UserSubController@ListAction');                                       // 小号列表
    Route::any('user_sub/new', 'Api\\UserSubController@NewAction');                                         // 添加小号
    Route::any('user_sub/game_list', 'Api\\UserSubController@GameListAction');                              // 玩家所有游戏的小号列表
    Route::any('user_sub/set_nickname', 'Api\\UserSubController@SetNicknameAction');                        // 设置小号昵称

    Route::any('pay/order/new', 'Api\\Pay\\OrderController@NewAction');                                     // 创建订单
    Route::any('pay/order/f/new', 'Api\\Pay\\OrderController@NewAction');                                   // XXX 4.0 充值F币的订单
    Route::any('pay/order/info', 'Api\\Pay\\OrderController@InfoAction');                                   // 获取订单信息
    Route::any('pay/order/config', 'Api\\Pay\\OrderController@ConfigAction');                               // 获取订单(支付）配置

    Route::any('pay/nowpay_wechat/request', 'Api\\Pay\\NowpayWechatController@RequestAction');              // 现在支付，微信
    Route::any('pay/wechat/request', 'Api\\Pay\\WechatController@RequestAction');                           // 微信
    Route::any('pay/alipay/request', 'Api\\Pay\\AlipayController@RequestAction');                           // 现在支付，支付宝
    Route::any('pay/unionpay/request', 'Api\\Pay\\UnionpayController@RequestAction');                       // 现在支付，银联
    Route::any('pay/f/request', 'Api\\Pay\\FController@RequestAction');                                     // 安锋支付，（帐户余额支付）
    Route::any('pay/mycard/request', 'Api\\Pay\\MycardController@RequestAction');                           // MyCard支付
    Route::any('pay/uc/request', 'Api\\Pay\\UcController@RequestAction');                                   // Uc平台支付
    Route::any('account/common/uc', 'Api\\Account\\CommonController@getUcAccountAction');                   // Uc平台获取用户信息
    Route::any('pay/googleplay/request', 'Api\\Pay\\GooglePlayController@RequestAction');                   // GooglePlay平台支付
    Route::any('pay/baidu/request', 'Api\\Pay\\BaiduController@RequestAction');                             // 百度平台支付
    Route::any('pay/yingyongbao/request', 'Api\\Pay\\YingYongBaoController@RequestAction');                 // 应用宝平台支付

    Route::any('ios/order/receipt/verify','Api\\Pay\\AppleController@validateReceiptAction');               // 验证苹果支付的信息
    Route::any('ios/order/create','Api\\Pay\\AppleController@OrderCreateAction');                           // 验证苹果支付的信息
    Route::any('ios/applelimit','Api\\Pay\\AppleController@AppleLimitAction');                              // 验证当前是否开启限制

    //Route::any('ios/order/receipt/verify','Api\\Pay\\AppleController@validateReceiptAction');               // XXX 4.0 验证苹果支付的信息
    //Route::any('ios/order/create','Api\\Pay\\OrderController@NewAction');                                   // XXX 4.0 验证苹果支付的信息
    //Route::any('ios/applelimit','Api\\Pay\\AppleController@AppleLimitAction');                              // XXX 4.0 验证当前是否开启限制

    Route::any('tool/reset_password/request','Api\\Tool\\ResetPasswordController@RequestAction');           // 通过token用户自行修改密码
    Route::any('tool/user/reset_password_page','Api\\Tool\\UserController@ResetPasswordPageAction');        // 获取重设密码页面
    Route::any('tool/user/freeze','Api\\Tool\\UserController@FreezeAction');                                // 冻结用户
    Route::any('tool/procedure/query','Api\\Tool\\ProcedureController@QueryAction');                        // 通过包名查询procedure
    Route::any('tool/user/auth', 'Api\\Tool\\AuthAccountController@AuthAccountAction');
    Route::any('tool/user_sub/freeze', 'Api\\Tool\\AuthAccountController@FreezeSubAction');
    
    Route::any('v1.0/cp/info/order','Api\\CP\\OrderController@GetOrderInfoAction');                          //获取订单信息
    Route::any('v1.0/cp/user/auth','Api\\CP\\UserController@CheckAuthAction');                               //验证登陆是否有效
});