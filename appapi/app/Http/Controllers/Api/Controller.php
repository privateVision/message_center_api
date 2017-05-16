<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Exceptions\Exception;
use App\Parameter;
use App\Model\Procedures;
use App\Model\ProceduresExtend;
use Illuminate\Http\Response;

class Controller extends \App\Controller
{
	protected $procedure = null;
	protected $procedure_extend = null;

	protected $request = null;
	protected $parameter = null;
	
	public function onError(Request $request, \Exception $e) {
	    if($e instanceof ApiException) {
	        log_warning('ApiException', ['code' => $e->getCode(), 'path' => $request->path(), 'reqdata' => $request->all()], $e->getMessage());
	        return response($e->getData(), 200)->withException($e);
	    } elseif($e instanceof Exception) {
	        log_warning('Exception', ['code' => $e->getCode(), 'path' => $request->path(), 'reqdata' => $request->all()], $e->getMessage());
	        return response($e->getData(), 200)->withException($e);
	    } else {
	        log_error('error', [
	            'message' => $e->getMessage(), 
	            'code' => $e->getCode(), 
	            'file' => $e->getFile(), 
	            'line' => $e->getLine(), 
	            'path' => $request->path(), 
	            'reqdata' => $request->all()
	        ]);

	        return response(null, 200)->withException(new Exception('system error', ApiException::Error));
	    }
	}
	
	public function onResponse(Request $request, Response $response) {
	    if($response->exception) {
	        $content = [
	            'code' => $response->exception->getCode(),
	            'msg' => $response->exception->getMessage(),
	            'data' => $response->getOriginalContent(),
	        ];
	    } else {
    	    $content = [
    	        'code' => ApiException::Success, 
    	        'msg' => null, 
    	        'data' => $response->getOriginalContent()
    	    ];
	    }
	    
	    $response->setContent($content);

	    return $response;
	}
	
    public function before(Request $request) {
        parent::before($request);

		$data = $request->all();

		$this->parameter = new Parameter($data);
		$_appid = $this->parameter->tough('_appid');
		$_sign = $this->parameter->tough('_sign');

		$this->procedure = Procedures::from_cache($_appid);
		if (!$this->procedure) {
			throw new ApiException(ApiException::Error, '"_appid" not exists:' . $_appid); // LANG:appid_missing
		}

		// -------- procedures_extend --------
		$this->procedure_extend = ProceduresExtend::find($_appid);
        if(!$this->procedure_extend) {
            $this->procedure_extend = new ProceduresExtend;
            $this->procedure_extend->pid = $_appid;
            $this->procedure_extend->service_qq = env('service_qq');
            $this->procedure_extend->service_page = env('service_page');
            $this->procedure_extend->service_phone = env('service_phone');
            $this->procedure_extend->service_share = env('service_share');
            $this->procedure_extend->heartbeat_data_refresh = 60000;
            $this->procedure_extend->heartbeat_interval = 2000;
            $this->procedure_extend->enable = 0x00000010 | 0x00000002; // 绑定手机（不强制）、实名（不强制）
            $this->procedure_extend->bind_phone_interval = 259200000;
            $this->procedure_extend->logout_img = env('logout_img');
            $this->procedure_extend->logout_redirect = env('logout_redirect');
            $this->procedure_extend->logout_inside = true;
            $this->procedure_extend->allow_num = 1;
            $this->procedure_extend->create_time = time();
            $this->procedure_extend->update_time = time();
            $this->procedure_extend->save();
        }
		// --------------- end ---------------

		$appkey = $this->procedure->appkey();
		
		unset($data['_sign']);
		ksort($data);
		$sign = md5(http_build_query($data) . '&key=' . $appkey);

		if($_sign !== $sign) {
			throw new ApiException(ApiException::Error, "签名验证失败"); // LANG:sign_verify_error
		}

		// --------- 平台登录特殊处理 ---------
		$__appid = $this->parameter->get('__appid');
		if($__appid) {
			$this->procedure = Procedures::from_cache($__appid);

			if(!$this->procedure){
				$this->procedure = Procedures::where("pid",$_appid)->first();
			}

			if (!$this->procedure) {
				throw new ApiException(ApiException::Error, "appid not found:{$_appid}"); // LANG:appid_missing
			}

			$this->parameter->set('_appid', $__appid);
		}

		$__rid = $this->parameter->get('__rid');
		if($__rid) {
			$this->parameter->set('_rid', $__rid);
		}
		
		// ------------------------------------
		$this->request = $request;
	}
}