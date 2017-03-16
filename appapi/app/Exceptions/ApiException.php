<?php
namespace App\Exceptions;

class ApiException extends \Exception
{
	const Success = 1;              // 成功
    const Remind = 0;               // 逻辑错误，该错误返回的msg消息可以直接反馈给用户
	const Error = 100;              // 错误，系统错误
    const AccountFreeze = 101;      // 帐号被冻结
    const Expire = 102;             // 会话已过期，遇到这个错误应该将玩家引导到登陆界面
    const OauthNotRegister = 103;   // 第三方openid尚未注册
    const MobileNotRegister = 104;  // 一键登陆在尚未收到短信回调时（一键登陆轮循继续）

	public function __construct($code, $message) {
		parent::__construct($message, $code);
	}
}