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
			$appkey = $procedure->appkey();

			$data = $request->all();
			$sign = @$data['sign'];
			unset($data['sign']);
			ksort($data);
			$_sign = md5(http_build_query($data) . '&key=' . $appkey);
			if($sign !== $_sign) {
				throw new ApiException(ApiException::Error, "签名验证失败");
			}

			if(trim($param) !== '') {
				$poststr = decrypt3des($param, $appkey);
				if ($poststr === false) {
					throw new ApiException(ApiException::Error, "参数无法解密");
				}

				parse_str($poststr, $postdata);
				$parameter = new Parameter($postdata);
			} else {
				$parameter = new Parameter([]);
			}

			log_debug('request', ['route' => $request->path(), 'appid' => $appid, 'param' => $postdata]);

			$this->before($request, $parameter);
			$response = $this->$action($request, $parameter);
			$this->after($request, $parameter);

			log_debug('response', $response);

			$resdata = array('code' => ApiException::Success, 'msg' => null, 'data' => $response);
		} catch (ApiException $e) {
			log_warning('ApiException', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
			$resdata = array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
		} catch (\App\Exceptions\Exception $e) {
			log_warning('Exception', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
			$resdata = array('code' => ApiException::Error, 'msg' => $e->getMessage(), 'data' => null);
		} catch(\Exception $e) {
			log_error('systemError', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			$resdata = array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
		}

		$type = $request->input('type');

		if($type === 'jsonp') {
			$callback = $request->input('callback');
			if($callback) {
				return sprintf('%s(%s);', $callback, json_encode($resdata));
			}
		} else {
			return $resdata;
		}
	}

	public function before(Request $request, Parameter $parameter) {

	}

	public function after(Request $request, Parameter $parameter) {

	}
}