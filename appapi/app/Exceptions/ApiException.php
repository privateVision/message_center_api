<?php
namespace App\Exceptions;

class ApiException extends Exception
{
    // 以下错误码所有接口都会返回
    const Success = 1;                  // 成功
    const Remind = 0;                   // 逻辑错误，该错误返回的msg消息可以直接反馈给用户
    const Error = 100;                  // 错误，系统错误
    const Expire = 102;                 // 会话已失效，需重新登录

    // 以下错误码在登录时会返回
    const AccountFreeze = 101;          // 帐号被冻结
    const UserSubFreeze = 108;          // 子账号被冻结

    // 以下接口只有在特定接口会返回
    const OauthNotRegister = 103;       // 第三方openid尚未注册
    const MobileNotRegister = 104;      // 一键登录在尚未收到短信回调时（一键登录轮循继续）
    const MobileBindOther = 105;        // 手机号码已经绑定
    const AlreadyBindOauth = 106;       // 账号已经绑定了某种平台账号，无法再次绑定
    const AlreadyBindMobile = 107;      // 账号已经绑定了手机号码，无法再次绑定
    const AlreadyBindOauthOther = 109;  // 平台账号已经绑定了其它账号
    const NotRealName = 110;            // 未实名制
    const AccountAbnormal = 111;        // 帐号异常
    
    protected $data = null;

	public function __construct($code, $message, $data = null) {
		parent::__construct($message, $code);
		$this->data = $data;
	}
	
	public function getData() {
	    return $this->data;
	}
}