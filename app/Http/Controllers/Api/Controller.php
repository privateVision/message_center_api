<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;

class Controller extends \App\Controller
{
    public function execute(Request $request, $action, $parameters) {
		try {
			// 3DES
			$postraw = file_get_contents('php://input');
			if(empty($postraw)) {
				throw new ApiException(ApiException::Error, "无法获取加密参数");
			}

			$poststr = decrypt3des($postraw);
			if($poststr === false) {
				throw new ApiException(ApiException::Error, "参数无法解密");
			}

			parse_str($poststr, $postdata);
			$parameter = new Parameter($postdata);

			$this->before($request, $parameter);
			$response = $this->$action($request, $parameter);
			$this->after($request, $parameter);

			return array('code' => ApiException::Success, 'msg' => null, 'data' => $response);
		} catch (ApiException $e) {
			return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
		} catch(\Exception $e) {
			return array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
		}
	}

	public function before($request, $parameter) {

	}

	public function after($request, $parameter) {

	}
}