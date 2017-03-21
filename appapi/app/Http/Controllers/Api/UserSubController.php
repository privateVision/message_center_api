<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\UserSub;

class UserSubController extends AuthController
{
    public function ListAction(Request $request, Parameter $parameter) {
        $pid = $parameter->tough('_appid');

        $data = [];
        $user_sub = UserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->orderBy('priority', 'desc')->get();
        foreach($user_sub as $v) {
            $data[] = [
                'openid' => $v->cp_uid,
                'name' => $v->name,
                'is_freeze' => $v->is_freeze,
                'is_default' => $v->id === $this->session->user_sub_id
            ];
        }

        return $data;
    }

    public function NewAction(Request $request, Parameter $parameter) {
        
    }
}