<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;

class TestController extends Controller
{
    public function TestAction(Request $request, Parameter $parameter) {
    	//var_dump($parameter);
    	//throw new \Exception('HelloWorld', 123);
    	return 3;
    }
}