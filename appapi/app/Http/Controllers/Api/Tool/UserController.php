<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\Session;

class UserController extends AuthController
{
    public function ResetPasswordPageAction() {
        $token = encrypt3des(json_encode(['ucid' => $this->user->ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= 'token=' . urlencode($token);

        return ['url' => $url, 'token' => $token];
    }

    public function FreezeAction() {
        $status = $this->parameter->tough('status');

        $is_freeze = $status > 0 ? true : false;

        $this->user->is_freeze = $is_freeze;
        $this->user->save();

        if($is_freeze) {
            $session = Session::from_cache_token($this->user->uuid);
            if($session) {
                $session->freeze = $is_freeze ? 1 : 0;
                $session->save();
            }
        }

        return ['result' => true];
    }
}