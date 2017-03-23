<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Redis;
use App\Model\Procedures;

class Controller extends \App\Controller
{
	protected $procedure = null;
	
    public function execute(Request $request, $action, $parameters) {
		try {
			$data = $request->all();

			$parameter = new Parameter($data);
			$appid = $parameter->tough('_appid');
			$sign = $parameter->tough('_sign');

			$procedure = Procedures::from_cache($appid);
			if (!$procedure) {
				throw new ApiException(ApiException::Error, "appid不正确:{$appid}");
			}

			$this->procedure = $procedure;
			$appkey = $procedure->appkey();
			
			unset($data['_sign']);
			ksort($data);
			$_sign = md5(http_build_query($data) . '&key=' . $appkey);

			if($sign !== $_sign) {
				throw new ApiException(ApiException::Error, "签名验证失败");
			}

			log_debug('request', ['route' => $request->path(), 'data' => $data]);

			$this->before($request, $parameter);
			$response = $this->$action($request, $parameter);
			$this->after($request, $parameter);

			log_debug('response', $response);

			return array('code' => ApiException::Success, 'msg' => null, 'data' => $response);
		} catch (ApiException $e) {
			log_warning('ApiException', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
			return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
		} catch (\App\Exceptions\Exception $e) {
			log_warning('Exception', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
			return array('code' => ApiException::Error, 'msg' => $e->getMessage(), 'data' => null);
		} catch(\Exception $e) {
			log_error('error', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			return array('code' => ApiException::Error, 'msg' => $e->getMessage(), 'data' => null);
		}
/*
		$type = $parameter->tough('_type');

		if($type === 'jsonp') {
			$callback = $parameter->tough('_callback');
			return sprintf('%s(%s);', $callback, json_encode($resdata));
		} else {
			return $resdata;
		}
*/
	}

	public function before(Request $request, Parameter $parameter) {

	}

	public function after(Request $request, Parameter $parameter) {

	}
}