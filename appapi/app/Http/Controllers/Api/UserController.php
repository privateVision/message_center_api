<?php
/*
* @Author: anchen
* @Date:   2017-02-17 18:28:02
* @Last Modified by:   anchen
* @Last Modified time: 2017-02-18 10:55:11
*/

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Parameter;
use App\Exceptions\ApiException;

use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Ucusers;
use App\Model\Orders;

use App\Event;

class UserController extends AuthController
{
    public function MessageAction(Request $request, Parameter $parameter) {
        return [];
    }

    public function LogoutAction(Request $request, Parameter $parameter) {
        Event::onLogout($this->ucuser, $this->session);
        return ['result' => true];
    }

    public function RechargeAction(Request $request, Parameter $parameter) {
        $order = Orders::where('vid', env('APP_SELF_ID'))->where('status', Orders::Status_Success)->get();

        $data = [];
        foreach($order as $v) {
            $data[] = [
                'order_id' => $v->sn,
                'fee' => $v->fee,
                'subject' => $v->subject,
                'otype' => 0, // todo: 这是什么鬼？
                'createTime' => strtotime($v->createTime),
                'status' => $v->status,
            ];
        }

        return $data;
    }


    /*
 * 获取短息验证码
 * @param  uid
 * $param mobile
 * return $code 生成的短信验证码
 * */
    public function getAuthCodeAction(Request $request,Parameter $parameter){
        $code = rand(111111,999999); #生成短信验证码
        $content  = trans_choice('messages.phone_unbind_code', $code);
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

    }

    /*
     * 获取用户的钱包的信息
     * */

    public function walletAction(Request $request,Parameter $parameter){
        return  ["wallet"=>$this->ucuser->balance];
    }




}