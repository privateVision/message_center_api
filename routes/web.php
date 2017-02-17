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

$app->group(['prefix' => 'api'], function () use ($app) {
	$app->post('test', 'Api\\TestController@TestAction');
	$app->post('app/initialize', 'Api\\AppController@InitializeAction');
});