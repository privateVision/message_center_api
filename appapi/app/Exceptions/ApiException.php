<?php
namespace App\Exceptions;

class ApiException extends \Exception
{
	const Success = 1;   // 成功
	const Error = 100;   // 错误，系统错误
	const Remind = 0;    // 逻辑错误，该错误返回的msg消息可以直接反馈给用户

	public function __construct($code, $message) {
		parent::__construct($message, $code);
	}
}