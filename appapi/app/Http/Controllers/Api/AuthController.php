<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\Ucuser;
use App\Model\UcuserInfo;

class AuthController extends Controller {

	protected $user = null;
	protected $user_info = null;
	protected $session = null;

	public function before() {
		parent::before();

		$token = $this->parameter->tough('_token');

		$user = Ucuser::from_cache_uuid($token);
		if(!$user) {
			throw new ApiException(ApiException::Remind, '账号已在其它地方登陆');
		}

		if($user->is_freeze) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}

		$session = Session::from_cache_token($token);
		if(!$session) {
			throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		}

		//$pid = $this->parameter->tough('_appid');
		//if($session->pid != $pid) {
		//	throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		//}

		$this->session = $session;
		$this->user = $user;
	}
}