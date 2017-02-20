<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;

class BaseController extends Controller {

	protected $session = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);
		$access_token = $parameter->tough('token');
		$session = Session::where('access_token', $access_token)->first();
		if(!$session) {
			throw new ApiException(ApiException::Error, 'session不正确');
		}

		$this->session = $session;
	}
<<<<<<< HEAD

	protected function getSession() {
		return $this->session;
	}
}
=======
}
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
