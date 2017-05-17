<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Http\Response;

class Basic
{
    public function handle(Request $request, Closure $next) {
        $request->route()->getController()->before($request);
        
        $response = $next($request);
        if(!$response->exception) {
            $response = $request->route()->getController()->onResponse($request, $response);
        }
        
        $request->route()->getController()->after($request, $response);
        
        return $response;
    }
}