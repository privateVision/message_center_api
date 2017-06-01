<?php
namespace App;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
	public function execute(Request $request, $action, $parameters) {
		array_unshift($parameters, $request);
		return call_user_func_array([$this, $action], $parameters);
	}
}