<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\UcuserSession;
use App\Model\Ucuser;
use App\Model\UcuserInfo;

class AuthController extends Controller {

	protected $user = null;
	protected $user_info = null;
	protected $session = null;

	public function before() {
		parent::before();

		$token = $this->parameter->tough('_token');
		if(!$token) {
			throw new ApiException(ApiException::Expire, '请先登陆');
		}

		$usession = UcuserSession::from_cache_session_token($token);
		if(!$usession) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登陆');
		}

		$user = Ucuser::from_cache($usession->ucid);
		if(!$user) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登陆');
		}

		$session = Session::from_cache_token($token);
		if(!$session) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登陆');
		}

		if($user->is_freeze) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}

		$this->session = $session;
		$this->user = $user;
	}
}