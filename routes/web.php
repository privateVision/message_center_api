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

$app->group(['prefix' => 'api'], function () use ($app) {
	$app->post('app/initialize', 'Api\\AppController@InitializeAction');
	$app->post('account/loginToken', 'Api\\AccountController@LoginTokenAction');
	$app->post('user/userRegister','Api\\UserController@userRegisterAction');
});
