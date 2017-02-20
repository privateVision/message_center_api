<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Exceptions\ApiException;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
        $imei = $parameter->tough('imei');
        $retailer = $parameter->tough('retailer');
    	$device_code = $parameter->tough('device_code');
    	$device_name = $parameter->tough('device_name');
    	$device_platform = $parameter->tough('device_platform');
    	$version = $parameter->tough('version');

    	$session = new Session;
    	$session->access_token = uuid();
        $session->imei = $imei;
        $session->retailer = $retailer;
    	$session->device_code = $device_code;
    	$session->device_name = $device_name;
    	$session->device_platform = $device_platform;
    	$session->version = $version;
    	$session->device_code = $device_code;
    	$session->expired_ts = time() + 2592000; // 1个月有效期
    	$session->save();

    	return array('token' => $session->access_token);
    }
}