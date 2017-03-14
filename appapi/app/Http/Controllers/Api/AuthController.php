<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\Ucusers;

class AuthController extends Controller {

	protected $ucuser = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);

		$token = $parameter->tough('_token');
		$session = Session::where('token', $token)->first();
		if(!$session) {
			throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		}

		$ucid = $session->ucid;
		if(!$ucid) {
			throw new ApiException(ApiException::Remind, '请先登陆');
		}

		$ucuser = Ucusers::find($ucid);
		if(!$ucuser) {
			throw new ApiException(ApiException::Error, '玩家未找到');
		}

		$this->ucuser = $ucuser;

		if(!$session->is_service_login && $this->ucuser->isFreeze()) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}
	}
}