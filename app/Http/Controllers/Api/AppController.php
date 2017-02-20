<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Session;
use App\Exceptions\ApiException;

class AppController extends Controller
{
    public function InitializeAction(Request $request, Parameter $parameter) {
<<<<<<< HEAD

        $imei = $parameter->tough('imei');
        $retailer = $parameter->tough('retailer');
      $device_code = $parameter->tough('device_code');
      $device_name = $parameter->tough('device_name');
      $device_platform = $parameter->tough('device_platform');
      $version = $parameter->tough('version');
=======
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
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3

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
