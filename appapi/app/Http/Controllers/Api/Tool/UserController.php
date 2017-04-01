<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;

class UserController extends Controller
{
    protected $user = null;

    public function ResetPasswordPageAction() {
        $token = encrypt3des(json_encode(['ucid' => $ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= 'token=' . $token;

        return ['url' => $url];
    }

    public function FreezeAction() {
        $status = $this->parameter->tough('status');
    }

    public function before() {
        $ucid = $this->parameter->tough('ucid');
        
        $user = Ucuser::from_cache($ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '用户不存在');
        }

        $this->user = $user;
    }
}
