<?php
use Illuminate\Http\Response;

define('SWOOLE', true);

require __DIR__ . '/bootstrap/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$http = new Swoole\Http\Server('127.0.0.1', 9501);
$http->on('request', function ($req, $res) use($app, $kernel) {
    $server = [];
    if(property_exists($req, 'server') && is_array($req->server)) {
    	foreach($req->server as $k=>$v) {
    		$server[strtoupper($k)] = $v;
    	}
    	
    	$server['ORIG_PATH_INFO'] = @$server['PATH_INFO'];
    }

    // handle
    $request = new Illuminate\Http\Request(
    	property_exists($req, 'get') ? $req->get : [], 
    	property_exists($req, 'post') ? $req->post : [], 
    	[], 
    	property_exists($req, 'cookie') ? $req->cookie : [], 
    	property_exists($req, 'files') ? $req->files : [], 
    	$server, 
    	$req->rawContent()
    );
    
	$response = $kernel->handle($request);
	
	if($response instanceof Response) {
    	// sent headers
    	$headers = $response->headers;
    	foreach ($headers->allPreserveCase() as $name => $values) {
    		foreach ($values as $value) {
    			$res->header($name, $value);
    		}
    	}
    	
    	// set cookie
    	foreach ($headers->getCookies() as $cookie) {
    		if ($cookie->isRaw()) {
    			// TODO encode cookie
    			$res->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
    		} else {
    			$res->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
    		}
    	}

    	// end
        $res->end($response->getContent());
	} else {
	    $res->end("");
	}
	
    $kernel->terminate($request, $response);
});

$http->start();