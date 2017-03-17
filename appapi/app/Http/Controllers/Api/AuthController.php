<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\User;

class AuthController extends Controller {

	protected $user = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);

		$token = $parameter->tough('_token');
		$session = Session::findFromToken($token);
		if(!$session) {
			throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		}

		$ucid = $session->ucid;
		if(!$ucid) {
			throw new ApiException(ApiException::Remind, '请先登陆');
		}

		$user = User::find($ucid);
		if(!$user) {
			throw new ApiException(ApiException::Error, '玩家未找到');
		}

		$this->user = $user;

		if($this->user->is_freeze) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}
	}
}