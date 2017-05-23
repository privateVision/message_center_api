<?php
Route::get('test','Web\\TestController@indexAction');
Route::any('login','Web\\TestController@loginAction');
Route::any('sign','Web\\TestController@signAction');
