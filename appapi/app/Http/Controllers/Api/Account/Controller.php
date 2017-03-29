<?php
namespace App\Http\Controllers\Api\Account;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Http\Controllers\Api\Controller as BaseController;

abstract class Controller extends BaseController {
    
    public function onLogin(User $user, UserSub $user_sub) {
        
    }
    
    public function onResetPassword() {

    }
    
}