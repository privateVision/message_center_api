<?php
Route::any('/test', function() {

});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
});