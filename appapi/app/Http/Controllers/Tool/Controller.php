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
            $postdata = empty($_POST)?$_GET:$_POST;
            $token = @$postdata['_token'];
            unset($postdata['_token']);
            ksort($postdata);
            $key = config('common.app_keys');
            $skey ='APP_' .($postdata['_appid']?$postdata['_appid']:1001);

            echo $key["$skey"];
            $_token = md5(http_build_query($postdata) . $key["$skey"]);

            if($_token !== $token) {
                throw new ToolException(ToolException::Error, 'token错误');
            }

           log_info('request', ['route' => $request->path(), 'appid' => $postdata["_appid"], 'param' => $postdata]);
            //$parameter = new Parameter($postdata);

            $this->before($request);
            $response = $this->$action($request, $postdata);
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