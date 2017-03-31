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
	protected $request = null;
	protected $parameter = null;
	
    public function execute(Request $request, $action, $parameters) {
		try {
			$data = $request->all();

			$this->parameter = new Parameter($data);
			$_appid = $this->parameter->tough('_appid');
			$_sign = $this->parameter->tough('_sign');

			$this->procedure = Procedures::from_cache($_appid);
			if (!$this->procedure) {
				throw new ApiException(ApiException::Error, "appid不正确:{$_appid}");
			}

			//$this->procedure = $procedure;
			$appkey = $this->procedure->appkey();
			
			unset($data['_sign']);
			ksort($data);
			$sign = md5(http_build_query($data) . '&key=' . $appkey);

			if($_sign !== $sign) {
				throw new ApiException(ApiException::Error, "签名验证失败");
			}

			log_info('request', $data, $request->path());

			// --------- 平台登陆特殊处理 ---------
			$__appid = $this->parameter->get('__appid');
			if($__appid) {
				$this->procedure = Procedures::from_cache($__appid);
				if (!$this->procedure) {
					throw new ApiException(ApiException::Error, "appid不正确:{$_appid}");
				}

				$this->parameter->set('_appid', $__appid);
			}

			$__rid = $this->parameter->get('__rid');
			if($__rid) {
				$this->parameter->set('_rid', $__rid);
			}
			// ------------------------------------
			$this->request = $request;
			$this->before(...array_values($parameters));
			$response = $this->$action(...array_values($parameters));
			$this->after(...array_values($parameters));

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
		$type = $this->parameter->tough('_type');

		if($type === 'jsonp') {
			$callback = $this->parameter->tough('_callback');
			return sprintf('%s(%s);', $callback, json_encode($resdata));
		} else {
			return $resdata;
		}
*/
	}

	public function before() {

	}

	public function after() {

	}
}