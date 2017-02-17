<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;

class TestController extends Controller
{
    public function TestAction(Request $request, Parameter $parameter) {
    	return 3;
    }
}