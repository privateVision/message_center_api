<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/16
 * Time: 13:50
 */
namespace App\Http\Controllers\Web;
use App\Exceptions\ToolException;
use App\Model\User;
use App\Parameter;
use Illuminate\Http\Request;


class AccountController extends Controller{
    /*
     * freeze 小号冻结，传递小号的ID
     * @param username string procedure int controller uid 操作人id controller name 操作人的姓名
     * */
    public function freezeSubAc(Request $request ,Parameter $parameter){
        if(!check_name($parameter->touch('uid'))) return trans("messages.user_message_notfound");

    }

    /*
     * unfreeze 小号解冻
     * @param username string  procedure int 小号id 操作人的uid 操作人的 username
     * */
    public function unfreezeSubAc(){

    }

    /*
     * 获取当前的账号的信息
     * */
    public function getUserInfo(){

    }


    /*
     * 安峰通行证账号冻结
     * */
    public function userFreeze(Request $request ,Parameter $parameter){
        $username = $parameter->tough("uid");
        $freezeType = $parameter->touch("freezeType");

        if(!check_name($username)) return trans("messages.user_type_error");
        if(!preg_match("/^[01]$/",$freezeType)) return trans("messages.param_type_error");
        $user = User::where("uid",$username)->frist();
        if(empty($user)) return trans("messages.user_not_found");

        $user ->setIsFreezeAttribute(1);
        $ret = $user->save();

        //controller log
        try{
            //修改的信息记录到日志
            $account_log = new  AccountLog();
            $account_log->ucid           = $user['ucid'];
            $account_log->username      = $user['uid'];
            $account_log->salt          = $user['salt'];
            $account_log->addtime       = date('Y-m-d H:i:s',time());
            $account_log->save();
        }catch(\Exception $e){

        }

        if($ret) return trans("messages.freeze_success");

        throw new ToolException(ToolException::Remind,trans("messages.freeze_failed"));

    }







}

