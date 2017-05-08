<?php
namespace App\Http\Controllers\Api\CP;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Procedures;

class Controller extends \App\Controller
{
    protected $procedure = null;
    protected $request = null;
    protected $parameter = null;

    public function execute(Request $request, $action, $parameters) {
        try {
            $data = $request->all();

            log_info('request', $data, $request->path());

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
            $this->before(...array_values($parameters));
            $response = $this->$action(...array_values($parameters));
            $this->after(...array_values($parameters));

            log_debug('response', $response);

            return array('code' => 0, 'msg' => null, 'data' => $response);
        } catch (ApiException $e) {
            $code = $e->getCode();
            $code = $code == 0 ? 1 : $code;

            log_warning('ApiException', ['code' => $code], $e->getMessage());
            return array('code' => $code, 'msg' => $e->getMessage(), 'data' => null);
        } catch (\App\Exceptions\Exception $e) {
            log_warning('Exception', ['code' => $e->getCode()], $e->getMessage());
            return array('code' => 1, 'msg' => $e->getMessage(), 'data' => null);
        } catch(\Exception $e) {
            log_error('error', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return array('code' => ApiException::Error, 'msg' => 'system error', 'data' => null);
        }

    }

    public function before() {

    }

    public function after() {

    }
}