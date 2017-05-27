<?php
namespace App;

use App\Exceptions\ApiException;
use App\Model\IpRefused;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 所有应用都必须继承自该类，并且不能覆盖此构造函数
 * App\Http\Middleware\Basic是一个贯穿整个系统的中间件，所有应用都必须加载此中间件
 * 在必要的时候可以复写其方法
 */
class Controller extends BaseController
{
    protected $starttime = 0;

    public function __construct() {
        $this->middleware(\App\Http\Middleware\Basic::class);
    }

    /**
     * 当抛出错误时调用此接口
     * @param Request $request
     * @param \Exception $e
     */
    public function onError(Request $request, \Exception $e) {

    }

    /**
     * 在返回数据时对数据进一步的处理
     * @param Request $request
     * @param mixed $data 调用Controller@Action方法时得到的值
     */
    public function onResponse(Request $request, Response $response) {
        return $response;
    }

    /**
     * 在调用 action之前调用（见Middleware::Base）
     * @param Request $request
     */
    public function before(Request $request) {
        $this->starttime = microtime(true);
        log_info('request', $request->all(), $request->path());

        // 封ip
        $data = IpRefused::where('ip', getClientIp())->first();
        if($data){
            throw new ApiException(ApiException::Error, trans('messages.ipfreeze'));
        }
    }

    /**
     * 在调用action之后返回数据之前调用（见Middleware::Base）
     * @param Request $request
     * @param Response $response
     */
    public function after(Request $request, Response $response) {
        $endtime = microtime(true);

        $resdata = $response->getOriginalContent();
        if($resdata instanceof View) {
            $resdata = $resdata->render();
        }

        log_debug('response', ['path' => $request->path(), 'reqdata' => $request->all(), 'resdata' => $resdata], bcsub($endtime, $this->starttime, 5));
    }
}