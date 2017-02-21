<?php

namespace App\Http\Controllers\Tool;

use Illuminate\Http\Request;
use App\Exceptions\ToolException;
use App\Parameter;

class Controller extends \App\Controller
{
    public function execute(Request $request, $action, Parameter $parameters) {
        try {
            $postdata = $_POST;

            $token = @$postdata['token'];
            unset($postdata['token']);
            ksort($postdata);

            $_token = md5(http_build_query($postdata) . env('TOOL_KEY'));

            if($_token !== $token) {
                throw ToolException(ToolException::Error, 'token错误');
            }

            $this->before($request, $parameter);
            $response = $this->$action($request, $parameter);
            $this->after($request, $parameter);

            return array('code' => ToolException::Success, 'msg' => null, 'data' => $response);
        } catch (ToolException $e) {
            return array('code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null);
        } catch(\Exception $e) {
            // todo: 打印这么详细的消息到客户端是不安全的，方便调试
            return array('code' => ToolException::Error, 'msg' => sprintf('%s in %s(%d)', $e->getMessage(), $e->getFile(), $e->getLine()), 'data' => null);
        }
    }

    public function before(Request $request, Parameter $parameter) {

    }

    public function after(Request $request, Parameter $parameter) {

    }

}
