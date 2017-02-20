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

$app->get('/test',function(){
						//getToken
						$ispost = true;
						$key = "4c6e0a99384aff934c6e0a99";
						$token_url = "http://127.0.0.1/api/app/initialize";
						$dats = sendrequest($token_url,$ispost,encrypt3des("device_code='222'&device_name='w'&device_platform='ww'&version=1.0&imei='11'&retailer='222'",$key));
						$st =  encrypt3des("device_code='222'&device_name='w'&device_platform='ww'&version=1.0&imei='11'&retailer='222'",$key);

						$dt = json_decode($dats);
						var_dump($dats);
						if(isset($dt) && $dt->code == 0){
							$token = $dt->data->token;
							$sendurl  = "http://127.0.0.1/api/app/userRegister";
							$data  = "name='mll'&age=12&fh=13578658&token=".$token;
							$dat = sendrequest($sendurl,$ispost,encrypt3des($data,$key));
							return $dat;
						}

	});




$app->group(['prefix' => 'api'], function () use ($app) {
	$app->post('test', 'Api\\TestController@TestAction');
	$app->post('app/initialize', 'Api\\AppController@InitializeAction');
	$app->post('app/loginToken', 'Api\\AccountController@LoginTokenAction');
	$app->post('app/userRegister','Api\\UserController@quicknameAction');
});
