<?php
Route::any('/test', function() {
    var_dump(\App\IPIP::find('58.48.190.12'));

    $ip = new \Naux\IpLocation\IpLocation(app()->databasePath() . '/qqwry.dat');
    var_dump($ip->getlocation('58.48.190.12'));
});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
});