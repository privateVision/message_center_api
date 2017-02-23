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

$app->get('test', 'TestController@TestAction');
// 云片手机短信回调
$app->post('yunpian/callback', 'YunpianController@CallbackAction');
// 现代支付，微信支付回调
$app->post('pay_callback/nowpay_wechat', 'PayCallback\\NowpayWechatController@CallbackAction');
// 现代支付，支付宝支付回调
$app->post('pay_callback/nowpay_alipay', 'PayCallback\\NowpayAlipayController@CallbackAction');
// 现代支付，银联支付回调
$app->post('pay_callback/nowpay_union', 'PayCallback\\NowpayUnionController@CallbackAction');

$app->group(['prefix' => 'api'], function () use ($app) {
	$app->post('app/initialize', 'Api\\AppController@InitializeAction');

	$app->post('account/loginToken', 'Api\\AccountController@LoginTokenAction');
    $app->post('account/login', 'Api\\AccountController@LoginAction');
    $app->post('account/logout', 'Api\\UserController@LogoutAction');
	$app->post('account/register', 'Api\\AccountController@RegisterAction');
    $app->post('account/username', 'Api\\AccountController@UsernameAction');
    $app->post('account/phoneLogin', 'Api\\AccountController@PhoneLoginAction');

    $app->post('pay/order/new', 'Api\\Pay\\OrderController@NewAction');
    $app->post('pay/nowpay/wechat', 'Api\\Pay\\NowpayController@WechatAction');
    $app->post('pay/nowpay/alipay', 'Api\\Pay\\NowpayController@AlipayAction');
    $app->post('pay/nowpay/union', 'Api\\Pay\\NowpayController@UnionAction');
});