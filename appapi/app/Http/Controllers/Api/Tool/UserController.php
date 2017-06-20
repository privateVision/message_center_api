<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\Procedures;
use Illuminate\Http\Request;
use App\Session;
use App\Model\Orders;
use App\Parameter;
use App\Model\Ucuser;
use App\Model\UcuserSession;

class UserController extends AuthController
{
    public function ResetPasswordPageAction() {
        $token = encrypt3des(json_encode(['ucid' => $this->user->ucid, 't' => time() + 900]));

        $url = env('reset_password_url');
        $url.= strpos($url, '?') === false ? '?' : '&';
        $url.= http_build_query([
            'token' => $token,
            'username' => $this->user->uid
        ]);

        return ['url' => $url, 'token' => $token];
    }

    public function FreezeAction() {
        $status = $this->parameter->tough('status');
        $comment = $this->parameter->get('comment');
        $admin_user = $this->parameter->tough('admin_user');

        $this->user->is_freeze = $status;
        $this->user->save();

        user_log($this->user, $this->procedure, 'freeze', '【账号状态改变】%s:%s，由%s操作', $status, $comment, $admin_user);

        $usession = UcuserSession::where('ucid', $this->user->ucid)->get();
        foreach($usession as $v) {
            $s = Session::find($v->session_token);
            if($s) {
                $s->freeze = $status;
                $s->save();
            }
        }

        return ['result' => true];
    }

}