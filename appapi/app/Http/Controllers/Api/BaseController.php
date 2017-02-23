<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;

class BaseController extends Controller {

	protected $session = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);
		$access_token = $parameter->tough('access_token');
		$session = Session::where('access_token', $access_token)->first();
		if(!$session) {
			throw new ApiException(ApiException::Error, 'session不正确');
		}

		$this->session = $session;
	}
}