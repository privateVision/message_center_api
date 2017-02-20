<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;

class AccountController extends BaseController {

    protected $session = null;

    public function LoginTokenAction(Request $request, Parameter $parameter) {
        
    }
}
