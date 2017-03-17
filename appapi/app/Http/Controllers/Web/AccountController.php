<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/16
 * Time: 13:50
 */
namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;
use Mockery\Generator\Parameter;

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

}

