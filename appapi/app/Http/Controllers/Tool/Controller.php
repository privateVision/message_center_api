<?php

namespace App\Http\Controllers\Tool;

use Illuminate\Http\Request;
use App\Exceptions\ToolException;
use App\Parameter;

class Controller extends \App\Controller
{
    protected $app;

    public function execute(Request $request, $action, $parameters) {
        try {
            // 两个公共参数：_appid, _token
            $data = $request->all();
            if(empty($data)){
                throw new ToolException(ToolException::Error, '数据为空'); // LANG:data_empty
            }
            $token = @$data['_token'];
            unset($data['_token']);
            ksort($data);

            if(!isset($data['_appid'])) {
                throw new ToolException(ToolException::Error, '缺少"_appid"'); // LANG:missing_appid
            }

            $this->app = config('common.apps.'.$data['_appid'], null);
            if(!$this->app) {
                throw new ToolException(ToolException::Error, '应用未授权'); // LANG:app_not_allow
            }

            $appkey = $this->app->appkey;

            $_token = md5(http_build_query($data) . $appkey);

            if($_token !== $token) {
                throw new ToolException(ToolException::Error, '"_token"错误'); // LANG:token_error
            }

            log_debug('request', ['route' => $request->path(), 'data' => $data]);

            unset($data['_appid']);
            $parameter = new Parameter($data);

            $this->before($request);
            $response = $this->$action($request, $parameter);
            $this->after($request);

            return array('code' => ToolException::Success, 'msg' => null, 'data' => $response);
        } catch (ToolException $e) {
            log_warning('ToolException', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
            return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
        } catch (\App\Exceptions\Exception $e) {
            log_warning('Exception', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
            return array('code' => ToolException::Error, 'msg' => $e->getMessage(), 'data' => null);
        } catch(\Exception $e) {
            log_error('error', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return array('code' => ToolException::Error, 'msg' => 'system error', 'data' => null);
        }
    }

    public function before(Request $request) {

    }

    public function after(Request $request) {

    }
}
