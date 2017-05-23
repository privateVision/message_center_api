<?php
//Route::get('/a', function() {
//    return http_build_query(['a' => false, 'b' => true, 'c' => null]);
//    //return redirect('http://www.baidu.com/', 200);
//});

Route::get('test','Web\\TestController@indexAction');
Route::any('login','Web\\TestController@loginAction');
Route::any('sign','Web\\TestController@signAction');