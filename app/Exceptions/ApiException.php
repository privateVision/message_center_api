<?php
namespace App\Exceptions;

class ApiException extends \Exception
{
	const Success = 0;
	const Error = 1;
	const Remind = 100;

	public function __construct($code, $message) {
		parent::__construct($message, $code);
	}



}