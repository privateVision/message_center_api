<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
    	$game_id = $parameter->tough('game_id');
    	$device_code = $parameter->tough('device_code');
    	$device_name = $parameter->tough('device_name');
    	$device_platform = $parameter->tough('device_platform');
    	$version = $parameter->tough('version');
    }
}