<?php
namespace App\Exceptions;

class PayCallbackException extends \Exception
{
    const Success = 0;
    const SystemError = 1;
    const OrderNotExists = 2;   // 订单不存在
    const OrderStatusError = 3; // 订单状态不对
    const SignError = 4;        // 签名错误
    const HandleError = 5;      // 订单处理失败

	public function __construct($code, $message) {
		parent::__construct($message, $code);
	}
}