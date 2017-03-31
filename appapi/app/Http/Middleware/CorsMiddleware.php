<?php
/**
 * Created by PhpStorm.
 * Ucuser: Administrator
 * Date: 2017/3/7
 * Time: 8:35
 */
namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', '*');

        // 判断请求头中是否包含ORIGIN字段
        if(isset($request->server()['HTTP_ORIGIN'])){
            $origin = $request->server()['HTTP_ORIGIN'];
            header('Access-Control-Allow-Origin: '.$origin);
            header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
        }

        return $response;
    }
}