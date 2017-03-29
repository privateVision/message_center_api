<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\ProceduresExtend;

class UserController extends Controller
{
    public function ResetPasswordPageAction(Request $request, Parameter $parameter) {
        $ucid = $parameter->tough('ucid');
        $token = encrypt3des(json_encode(['ucid' => $ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= 'token=' . $token;

        return ['url' => $url];
    }
}
