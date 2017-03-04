<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Procedures;

class Controller extends \App\Controller
{
	protected $procedure = null;
	
    public function execute(Request $request, $action, $parameters) {
		try {
			// 3DES
			$appid = $request->input('appid');
			$param = $request->input('param');

			$procedure = Procedures::find($appid);
			if (!$procedure) {
				throw new ApiException(ApiException::Error, "appid不正确:" . $appid);
			}

			$this->procedure = $procedure;

			if (empty($param)) {
				throw new ApiException(ApiException::Error, "无法获取加密参数");
			}

			// todo: deskey是动态生成的，对性能有一点影响
			$poststr = decrypt3des($param, $procedure->deskey());
			if ($poststr === false) {
				throw new ApiException(ApiException::Error, "参数无法解密");
			}

			// todo: 多一步parse_str，差评。想想更好的、parse效率更高的数据格式
			parse_str($poststr, $postdata);
			$parameter = new Parameter($postdata);

			log_info('request', ['route' => $request->path(), 'appid' => $appid, 'param' => $postdata]);

			$this->before($request, $parameter);
			$response = $this->$action($request, $parameter);
			$this->after($request, $parameter);

			log_info('response', $response);

			return array('code' => ApiException::Success, 'msg' => null, 'data' => $response);
		} catch (ApiException $e) {
			log_warning('ApiException', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
			return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
		} catch(\Exception $e) {
			log_error('Exception', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			return array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
		}
	}

	public function before(Request $request, Parameter $parameter) {

	}

	public function after(Request $request, Parameter $parameter) {

	}
}