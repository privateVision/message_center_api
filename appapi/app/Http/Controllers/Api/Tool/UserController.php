<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;

class UserController extends Controller
{
    protected $user = null;

    public function ResetPasswordPageAction(Request $request, Parameter $parameter) {
        $ucid = $parameter->tough('ucid');

        $token = encrypt3des(json_encode(['ucid' => $ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= 'token=' . $token;

        return ['url' => $url];
    }

    public function FreezeAction(Request $request, Parameter $parameter) {
        $ucid = $parameter->tough('ucid');
    }

    public function before(Request $request, Parameter $parameter) {
        $ucid = $parameter->tough('ucid');
        
        $user = Ucuser::from_cache($ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '用户不存在');
        }

        $this->user = $user;
    }
}
