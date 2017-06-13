<?php
Route::any('/test', function() {
    //var_dump(config('sdkapi.anfan.com.common'));
    //config('common');
    //\App\Model\Ucuser::find(1);
});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
});