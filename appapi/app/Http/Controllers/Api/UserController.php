<?php
/*
* @Author: anchen
* @Date:   2017-02-17 18:28:02
* @Last Modified by:   anchen
* @Last Modified time: 2017-02-18 10:55:11
*/

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Model;
use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Ucusers;
use Illuminate\Http\Request;
use App\Parameter;
use App\Event;
use Illuminate\Support\Facades\Cache;

class UserController extends AuthController
{
    const SMS_LIMIT = 3;

    public function LogoutAction(Request $request, Parameter $parameter) {
        Event::onLogout($this->ucuser, $this->session);
        return ['result' => true];
    }


    /*
 * 获取短息验证码
 * @param  uid
 * $param mobile
 * return $code 生成的短信验证码
 * */
    public function getAuthCodeAction(Request $request,Parameter $parameter){
        $code = rand(111111,999999); #生成短信验证码
        $content  = trans('messages.phone_unbind_code').$code;
        send_sms($this->session->mobile,$content,$code);
    }

    /*
     * 手机解绑
     * @param $mobile
     * */

    public function unbindAction(Request $request,Parameter $parameter){
        $mobile = $this->session->mobile;
        $code = $request->input('code');

        if(!preg_match('/^\d{6}$/',$code)) throw new ApiException(ApiException::Remind, "验证码格式不正确！");

        $chars = 'abcdefghjkmnpqrstuvwxy';

        if($this->uucuser->mobile == $this->ucuser->uid){
            $username = Ucusers::username();
            $this->ucuser->ucenter_members->username = $username;
            $this->ucuser->ucenter_members->save();
            $content = trans_choice('messages.phone_unbind_code', $username);
            send_sms($this->ucuser->mobile,$content);
            $this->ucuser->uid= $username;
            $this->ucuser->save();
        }else{

        }

        UcenterMembers::where("username",$this->ucuser->uid)->get();
    }

    /*
     * 手机绑定短信
     * */
    public function bind(Request $request ,Parameter $parameter){
        $mobile = $request->input("mobile");

        if(!check_mobile($mobile)){
            throw new ApiException(ApiException::Remind,trans("messages.mobile_type_error"));
        }

        $code = rand(111111,999999);
        $content = trans("messages.sms_code").$code;

        //发送短信验证码限制 防止短信炸弹
        $rkey = $mobile.":".$this->code;
        $numj = Cache::get($rkey);

        if(!$numj ) {
            Cache::store("redis")->put($rkey, 1, 60 * 24); //保存一天 一天内发送短信的次数的限制
        }
        if($numj == self::SMS_LIMIT){
            throw  new ApiException(ApiException::Remind,trans("messages.sms_limit_code"));
        }else{
            Cache::increment($rkey); //发送短信次数增加
        }

        send_sms($mobile, $content,$code);
        return ['code'=>$code];
    }

    /*
     * 获取用户的钱包的信息
     * */

    public function walletAction(Request $request,Parameter $parameter){
        return  ["wallet"=>$this->ucuser->balance];
    }

    


}