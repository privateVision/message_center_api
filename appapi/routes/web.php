<?php
Route::any('/test', function(\Illuminate\Http\Request $request) {
    //putenv('TEST='.$request->get('t'));
    //sleep(5);
    //echo getenv('TEST');
    //$GLOBALS['TEST'] += 1;
    //sleep(5);
    //return 'hello world';//strval($GLOBALS['TEST']);
    //var_dump(@$GLOBALS['TEST']);
    //return 'hello world';
});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
    Route::get('qrtest', 'Web\\QrcodeController@testAction');
    Route::get('qrcode', 'Web\\QrcodeController@getAction');
});