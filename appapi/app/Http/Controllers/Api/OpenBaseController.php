<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Procedures;

class OpenBaseController extends \App\Controller
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
            $open_id = $this->parameter->tough('open_id');

            $this->procedure = Procedures::from_cache($_appid);
            if(!$this->procedure){
                $this->procedure = Procedures::where("pid",$_appid)->first();
            }
            if (!$this->procedure) {
                throw new ApiException(ApiException::Error, "appid not exists:{$_appid}");
            }

            $appkey = $this->procedure->psingKey;

            unset($data['sign']);
            log_debug('response', http_build_query($data)."&sign_key={$appkey}");

            // ksort($data);
            //  $sign = md5(http_build_query($data) ."&sign_key={$appkey}");

            ksort($data);
            $sign = md5(http_build_query($data) ."&sign_key={$appkey}");
            log_debug('response', $sign);
            if($_sign != $sign) {
                throw new ApiException(ApiException::Error, "签名验证失败");
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
            return array('code' => ApiException::Remind, 'msg' => $e->getMessage(), 'data' => null);
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