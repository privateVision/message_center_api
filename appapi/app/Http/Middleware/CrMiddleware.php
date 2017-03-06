<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/6
 * Time: 18:48
 */
namespace App\Http\Middleware;

use Closure;
use Response;

class CrMiddleware {
    public function handle($request,Closure $next){
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin',"*");
        $response->header('Access-Control-Allow-Methods',"GET,POST,PATCH,PUT,OPTIONS");
        $response->header('Access-Control-Allow-Headers',"Origin,Content-Type,Cookie,Accept");
        $response->header('Access-Control-Allow-Credentials',"false");
        return $response;
    }

}