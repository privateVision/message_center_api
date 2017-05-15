<?php
namespace App\Http\Controllers\Api\CP;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Exceptions\Exception;
use App\Parameter;
use App\Model\Procedures;
use Illuminate\Http\Response;

class Controller extends \App\Controller
{
    protected $procedure = null;
    protected $request = null;
    protected $parameter = null;

    public function before(Request $request) {
        parent::before($request);
        
        $data = $request->all();

        $this->parameter = new Parameter($data);

        $_appid = $this->parameter->tough('app_id');
        $_sign = $this->parameter->tough('sign');

        $this->procedure = Procedures::from_cache($_appid);
        if (!$this->procedure) {
            throw new ApiException(ApiException::Error, '"_appid" not exists:' . $_appid);
        }

        $appkey1 = $this->procedure->psingKey;
        $appkey2 = $this->procedure->appkey();

        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= "{$k}={$v}&";
        }

        if($_sign !== md5("{$str}sign_key={$appkey1}") && $_sign !== md5("{$str}sign_key={$appkey2}")) {
            throw new ApiException(ApiException::Error, "签名验证失败");
        }

        // ------------------------------------
        $this->request = $request;
    }

    public function onError(Request $request, $e) {
        if($e instanceof ApiException) {
            $code = $e->getCode();
            $code = $code == 0 ? 1 : $code;
            
            log_warning('ApiException', ['code' => $code], $e->getMessage());
            return array('code' => $code, 'msg' => $e->getMessage(), 'data' => null);
        } elseif($e instanceof Exception) {
            log_warning('Exception', ['code' => $e->getCode()], $e->getMessage());
            return array('code' => 1, 'msg' => $e->getMessage(), 'data' => null);
        } else {
            log_error('error', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
        }
    }
    
    public function onResponse(Request $request, $data) {
        return array('code' => 0, 'msg' => null, 'data' => $response);
    }
}