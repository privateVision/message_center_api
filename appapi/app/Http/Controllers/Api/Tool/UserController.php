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
use App\Model\TotalFeePerUser;
use App\Model\UcuserSub;
use App\Model\UcuserRole;

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
        $admin_user = $this->parameter->tough('admin_user');

        $is_freeze = $status > 0 ? true : false;
        if($is_freeze) {
            $comment = $this->parameter->tough('comment');
        }

        $this->user->is_freeze = $is_freeze;
        $this->user->save();

        if($is_freeze) {
            user_log($this->user, $this->procedure, 'freeze', '【冻结账号】%s，由%s操作', $comment, $admin_user);
        } else {
            user_log($this->user, $this->procedure, 'unfreeze', '【解冻账号】由%s操作', $admin_user);
        }

        $usession = UcuserSession::where('ucid', $this->user->ucid)->get();
        foreach($usession as $v) {
            $s = Session::find($v->session_token);
            if($s) {
                $s->freeze = $is_freeze ? 1 : 0;
                $s->save();
            }
        }

        return ['result' => true];
    }

}