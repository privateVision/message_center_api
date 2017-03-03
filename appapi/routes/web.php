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
    http_response_code(404); exit;
});

$app->get('test', 'TooltestController@fpayTestAction');                                                         // test
$app->post('yunpian/callback', 'YunpianController@CallbackAction');                                     // 云片手机短信回调
$app->post('pay_callback/nowpay_wechat', 'PayCallback\\NowpayWechatController@CallbackAction');         // 现代支付，微信支付回调
$app->post('pay_callback/nowpay_alipay', 'PayCallback\\NowpayAlipayController@CallbackAction');         // 现代支付，支付宝支付回调
$app->post('pay_callback/nowpay_unionpay', 'PayCallback\\NowpayUnionpayController@CallbackAction');     // 现代支付，银联支付回调

$app->group(['prefix' => 'api'], function () use ($app) {
    $app->post('app/initialize', 'Api\\AppController@InitializeAction');                                // 初始化
    $app->post('account/login_token', 'Api\\AccountController@LoginTokenAction');                       // 自动登陆
    $app->post('account/login', 'Api\\AccountController@LoginAction');                                  // 用户名或手机号码登陆
    $app->post('account/logout', 'Api\\UserController@LogoutAction');                                   // 退出登录
    $app->post('account/register', 'Api\\AccountController@RegisterAction');                            // 用户名注册
    $app->post('account/username', 'Api\\AccountController@UsernameAction');                            // 生成随机用户名
    $app->post('account/login_phone', 'Api\\AccountController@LoginPhoneAction');                       // 手机号码一键登陆
    $app->post('pay/order/new', 'Api\\Pay\\OrderController@NewAction');                                 // 创建订单
    $app->post('pay/order/self_new', 'Api\\Pay\\OrderController@SelfNewAction');                        // 创建充值平台币的订单
    $app->post('pay/nowpay/wechat', 'Api\\Pay\\NowpayController@WechatAction');                         // 现在支付，微信
    $app->post('pay/nowpay/alipay', 'Api\\Pay\\NowpayController@AlipayAction');                         // 现在支付，支付宝
    $app->post('pay/nowpay/unionpay', 'Api\\Pay\\NowpayController@UnionpayAction');                     // 现在支付，银联
});

$app->group(['prefix' => 'tool'], function () use ($app) {
    $app->get('user/fpay', 'Tool\\UserController@fpayAction');                                          //F币支付
    $app->post('user/freeze', 'Tool\\UserController@freezeAction');                                      //账户冻结
    $app->get('user/unfreeze', 'Tool\\UserController@unfreezeAction');                                   //解冻
    $app->get('user/auth', 'Tool\\UserController@authorizeAction');                                     //用户验证
    $app->get("user/sendsms",'Tool\\UserController@sendmsAction');                                      //发送短信验证码
    $app->get("user/authsms",'Tool\\UserController@authsmsAction');                                     //验证码验证
});