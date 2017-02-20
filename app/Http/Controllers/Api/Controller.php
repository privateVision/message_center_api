<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;

class Controller extends \App\Controller
{
<<<<<<< HEAD
=======
	protected $procedure = null;

>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
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
<<<<<<< HEAD
			return array('code' => ApiException::Error, 'msg' => $e->getMessage(),'data' => null);
=======
			// todo: 打印这么详细的消息到客户端是不安全的，方便调试
			return array('code' => ApiException::Error, 'msg' => sprintf('%s in %s(%d)', $e->getMessage(), $e->getFile(), $e->getLine()), 'data' => null);
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
		}
	}

	public function before(Request $request, Parameter $parameter) {

	}

	public function after(Request $request, Parameter $parameter) {

	}
<<<<<<< HEAD
}
=======
}
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
