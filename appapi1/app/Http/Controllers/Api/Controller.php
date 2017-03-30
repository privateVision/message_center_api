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
			$_appid = $parameter->tough('_appid');
			$_sign = $parameter->tough('_sign');

			$procedure = Procedures::from_cache($_appid);
			if (!$procedure) {
				throw new ApiException(ApiException::Error, "appid不正确:{$_appid}");
			}

			//$this->procedure = $procedure;
			$appkey = $procedure->appkey();
			
			unset($data['_sign']);
			ksort($data);
			$sign = md5(http_build_query($data) . '&key=' . $appkey);

			if($_sign !== $sign) {
				throw new ApiException(ApiException::Error, "签名验证失败");
			}

			log_info('request', $data, $request->path());

			// todo: 第三方登陆通过_appid作签名验证，实际算__appid的
			$__appid = $parameter->get('__appid');
			if($__appid) {
				$procedure = Procedures::from_cache($__appid);
				if (!$procedure) {
					throw new ApiException(ApiException::Error, "appid不正确:{$_appid}");
				}

				$parameter->set('_appid', $__appid);
			}

			$__rid = $parameter->get('__rid');
			if($__rid) {
				$parameter->set('_rid', $__rid);
			}

			$this->procedure = $procedure;
			// ---------------- end --------------

			$this->before($request, $parameter);
			$response = $this->$action($request, $parameter);
			$this->after($request, $parameter);

			log_debug('response', $response);

			return array('code' => ApiException::Success, 'msg' => null, 'data' => $response);
		} catch (ApiException $e) {
			log_warning('ApiException', ['code' => $e->getCode()], $e->getMessage());
			return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
		} catch (\App\Exceptions\Exception $e) {
			log_warning('Exception', ['code' => $e->getCode()], $e->getMessage());
			return array('code' => ApiException::Error, 'msg' => $e->getMessage(), 'data' => null);
		} catch(\Exception $e) {
			log_error('error', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			return array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
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