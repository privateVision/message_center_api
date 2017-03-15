<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    //http_response_code(404); exit;
    //return \App\Model\UcuserProcedure::section(15)->find(111);
});

$app->get('test', 'TooltestController@fpayTestAction');// test
$app->get('createuser','TestController@createUserAction');


// 支付回调相关
$app->group(['prefix' => 'pay_callback'], function () use ($app) {
    $app->post('nowpay_wechat', 'PayCallback\\NowpayWechatController@CallbackAction');                  // 现代支付，微信支付回调
    $app->post('nowpay_alipay', 'PayCallback\\NowpayAlipayController@CallbackAction');                  // 现代支付，支付宝支付回调
    $app->post('nowpay_unionpay', 'PayCallback\\NowpayUnionpayController@CallbackAction');              // 现代支付，银联支付回调
});

// 对外公开（无限制的）功能（杂项）
$app->group(['prefix' => 'pub'], function () use ($app) {
    $app->post('yunpian/callback', 'Pub\\YunpianController@CallbackAction');                            // 云片手机短信回调
});

// API接口
$app->group(['prefix' => 'api'], function () use ($app) {
    $app->post('app/initialize', 'Api\\AppController@InitializeAction');                                // 初始化

    $app->post('account/login_token', 'Api\\AccountController@LoginTokenAction');                       // 自动登录
    $app->post('account/login', 'Api\\AccountController@LoginAction');                                  // 用户名或手机号码登陆
    $app->post('account/register', 'Api\\AccountController@RegisterAction');                            // 用户名注册
    $app->post('account/username', 'Api\\AccountController@UsernameAction');                            // 生成随机用户名
    $app->post('account/login_phone', 'Api\\AccountController@LoginPhoneAction');                       // 手机号码一键登陆
    $app->post('account/sms_token', 'Api\\AccountController@SMSTokenAction');                           // 手机号码一键登陆(获取发送短信的token)
    $app->post('account/sms_reset_password', 'Api\\AccountController@SMSResetPasswordAction');          // 发送重设密码的验证码

    $app->post('user/logout', 'Api\\UserController@LogoutAction');                                      // 退出登录
    $app->post('user/message', 'Api\\UserController@MessageAction');                                    // 消息轮循
    $app->post('user/recharge', 'Api\\UserController@RechargeAction');                                  // 充值记录（充F币）
    $app->post('user/consume', 'Api\\UserController@ConsumeAction');                                    // 消费记录
    $app->post('user/hide_order', 'Api\\UserController@HideOrderAction');                               // 隐藏订单

    $app->post('pay/order/new', 'Api\\Pay\\OrderController@NewAction');                                 // 创建订单
    $app->post('pay/order/anfeng/new', 'Api\\Pay\\OrderController@AnfengNewAction');                    // 充值F币的订单
    $app->post('pay/nowpay/wechat', 'Api\\Pay\\NowpayController@WechatAction');                         // 现在支付，微信
    $app->post('pay/nowpay/alipay', 'Api\\Pay\\NowpayController@AlipayAction');                         // 现在支付，支付宝
    $app->post('pay/nowpay/unionpay', 'Api\\Pay\\NowpayController@UnionpayAction');                     // 现在支付，银联
    $app->post('pay/anfeng/request', 'Api\\Pay\\AnfengController@RequestAction');                       // 安锋支付，（帐户余额支付）
});

// 对内部调用的API接口
$app->group(['prefix' => 'tool'], function () use ($app) {
    $app->post('sms/send', 'Tool\\SMSController@SendAction');                                           // 发送短信
    $app->get('sms/verify', 'Tool\\SMSController@VerifyAction');                                        // 验证短信码是否正确

    $app->post('user/fpay', 'Tool\\UserController@fpayAction');                                          //F币支付
    $app->post('user/freeze', 'Tool\\UserController@freezeAction');                                      //账户冻结
    $app->post('user/unfreeze', 'Tool\\UserController@unfreezeAction');                                   //解冻
    $app->post('user/auth', 'Tool\\UserController@authorizeAction');                                     //用户验证
    $app->post("user/sendsms",'Tool\\UserController@sendmsAction');                                      //发送短信验证码
    $app->post("user/authsms",'Tool\\UserController@authsmsAction');                                     //验证码验证
});