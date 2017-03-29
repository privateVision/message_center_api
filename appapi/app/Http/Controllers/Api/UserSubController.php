<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Redis;
use App\Model\ProceduresExtend;
use App\Model\UserSub;

class UserSubController extends AuthController
{
    public function ListAction(Request $request, Parameter $parameter) {
        $pid = $parameter->tough('_appid');

        $data = [];
        $user_sub = UserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->orderBy('name', 'asc')->get();
        foreach($user_sub as $v) {
            $data[] = [
                'id' => $v->id,
                'openid' => $v->cp_uid,
                'name' => $v->name,
                'is_freeze' => $v->is_freeze,
                'is_unused' => $v->last_login_at ? false : true,
                'is_default' => $v->id === $this->session->user_sub_id,
                'last_login_at' => strval($v->last_login_at),
            ];
        }

        return $data;
    }

    public function NewAction(Request $request, Parameter $parameter) {
        $pid = $parameter->tough('_appid');
        $rid = $parameter->tough('_rid');

        $config = ProceduresExtend::from_cache($pid);
        $allow_num = $config->allow_num ?: 1;

        $redisfield = $this->user->ucid .'_'. $pid;
        $user_sub_num = Redis::hget(Redis::KH_USERSUB_NUM, $redisfield);
        if(!$user_sub_num) {
            $reset = true;
            $user_sub_num = UserSub::tableSlice($this->user->ucid)->where('ucid', $this->user->ucid)->where('pid', $pid)->count();
        }

        if($allow_num <= $user_sub_num) {
            throw new ApiException(ApiException::Remind, "小号创建数量已达上限");
        }

        $user_sub = UserSub::tableSlice($this->user->ucid);
        $user_sub->id = uuid($this->user->ucid);
        $user_sub->ucid = $this->user->ucid;
        $user_sub->pid = $pid;
        $user_sub->rid = $rid;
        $user_sub->old_rid = $rid;
        $user_sub->cp_uid = uuid();
        $user_sub->name = "小号" . sprintf('%02d', $user_sub_num + 1);
        $user_sub->priority = 0;
        $user_sub->last_login_at = null;
        $user_sub->save();

        if(isset($reset)) {
            Redis::hset(Redis::KH_USERSUB_NUM, $redisfield, $user_sub_num + 1);
        } else {
            Redis::hincrby(Redis::KH_USERSUB_NUM, $redisfield, 1);
        }

        return [
            'id' => $user_sub->id,
            'openid' => $user_sub->cp_uid,
            'name' => $user_sub->name,
            'is_freeze' => false,
            'is_unused' => true,
            'is_default' => false,
            'last_login_at' => "",
        ];
    }
}