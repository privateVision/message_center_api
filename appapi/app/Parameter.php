<?php
namespace App;

use App\Exceptions\Exception;

class Parameter
{
	protected $_data;

	public function __construct($data) {
		$this->_data = $data;
	}

	public function set($key, $value) {
		$this->_data[$key] = $value;
	}

	public function get($key, $default = null, $type_fun_regex = null) {
		$data = @$this->_data[$key];
		if($data === null || $data === '') {
			return $default;
		}

		if(is_string($type_fun_regex) && method_exists($this, $type_fun_regex)) {
			return $this->$type_fun_regex($data);
		}

		if(is_callable($type_fun_regex)) {
			return $type_fun_regex($data);
		}

		if(is_string($type_fun_regex)) {
			if(preg_match($type_fun_regex, $data)) {
				return $data;
			} else {
				throw new Exception ("参数\"{$key}\"格式不正确", 0);
			}
		}

		return $data;
	}

	public function tough($key, $type_fun_regex = null) {
		$data = @$this->_data[$key];
		if($data === null || $data === '') {
			throw new Exception ('param is missing:"'.$key.'"', 0);
		}

		if(is_string($type_fun_regex) && method_exists($this, $type_fun_regex)) {
			return $this->$type_fun_regex($data);
		}

		if(is_callable($type_fun_regex)) {
			return $type_fun_regex($data);
		}

		if(is_string($type_fun_regex)) {
			if(preg_match($type_fun_regex, $data)) {
				return $data;
			} else {
				throw new Exception ("参数\"{$key}\"格式不正确", 0);
			}
		}

		return $data;
	}

	protected function mobile($mobile) {
		$mobile = trim($mobile);

		if(!preg_match('/^1\d{10}$/', $mobile)) {
			throw new Exception ("\"{$mobile}\" 不是一个有效的手机号码", 0);
		}
		
		return $mobile;
	}

	protected function username($username) {
		$username = trim($username);

		if(preg_match('/^\d+$/', $username)) {
			throw new Exception ("用户名错误，不能为纯数字", 0);
		}
		
		return $username;
	}

	protected function smscode($smscode) {
		$smscode = trim($smscode);

		if(strlen($smscode) != 6) {
			throw new Exception ("验证码错误", 0);
		}
		
		return $smscode;
	}

	protected function url($url) {
		$url = trim($url);

		if(!preg_match('/^https*:\/\/.*$/', $url)) {
			throw new Exception ("\"{$mobile}\" url错误", 0);
		}
		
		return $url;
	}

	protected function password($password) {
		$password = trim($password);
		if($password == "") throw new Exception ("密码不能为空", 0);

		return $password;
	}
}
