<?php
namespace App;

use App\Exceptions\ApiException;

class Parameter
{
	protected $_data;

	public function __construct($data) {
		$this->_data = $data;
	}

	public function get($key, $default = null) {
		$data = @$this->_data[$key];
		if($data === null) {
			return $default;
		}

		return $data;
	}

	public function tough($key) {
		$data = @$this->_data[$key];
		if($data === null) {
			throw new ApiException (ApiException::Error, 'param is missing:'.$key);
		}

		return $data;
	}
}
