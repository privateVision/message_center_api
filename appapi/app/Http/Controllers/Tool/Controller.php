<?php

namespace App\Http\Controllers\Tool;

use Illuminate\Http\Request;
use App\Exceptions\ToolException;
use App\Parameter;
use Illuminate\Support\Facades\Config;

class Controller extends \App\Controller
{
    public function execute(Request $request, $action, $parameters) {
        try {
            // 两个公共参数：_appid, _token
            $data = $_POST ?: $_GET;
            if(empty($data)){
                throw new ToolException(ToolException::Error, '参数为空');
            }

            $token = @$data['_token'];
            unset($data['_token']);
            ksort($data);

            $app = config('common.apps.' . $data['_appid']);
            if(!$app) {
                throw new ToolException(ToolException::Error, '缺少参数"_appid"');
            }

            $_token = md5(http_build_query($data) . $app['appkey']);

            if($_token !== $token) {
                throw new ToolException(ToolException::Error, '_token 错误');
            }

           log_info('request', ['route' => $request->path(), 'appid' => $data["_appid"], 'param' => $data]);

            $this->before($request);
            $response = $this->$action($request, $data);
            $this->after($request);

            return array('code' => ToolException::Success, 'msg' => null, 'data' => $response);
        } catch (ToolException $e) {
            log_error('requestError', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
            return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
        } catch (ApiException $e) {
            log_error('requestError', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
            return array('code' => ToolException::Error, 'msg' => $e->getMessage(), 'data' => null);
        } catch(\Exception $e) {
            echo $e->getMessage();
            log_error('systemError', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return array('code' => ToolException::Error, 'msg' => 'system error', 'data' => null);
        }
    }

    public function before(Request $request) {

    }

    public function after(Request $request) {

    }
}