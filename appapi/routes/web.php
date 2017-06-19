<?php
Route::any('/test', function() {
    //http_curl('http://192.168.1.101/test/2.php');
    //var_dump(json_decode('null', true));
    ip2location('106.'.rand(1,255).'.'.rand(1,255).'.190');
});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
    Route::get('qrtest', 'Web\\QrcodeController@testAction');
    Route::get('qrcode', 'Web\\QrcodeController@getAction');
});