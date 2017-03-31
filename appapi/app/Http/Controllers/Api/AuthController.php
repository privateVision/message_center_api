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
	protected $ucuser_info = null;
	protected $session = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);

		$token = $parameter->tough('_token');
		$session = Session::from_cache_token($token);
		if(!$session) {
			throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		}

		$pid = $parameter->tough('_appid');
		if($session->pid != $pid) {
			throw new ApiException(ApiException::Remind, '会话未找到，或已过期');
		}

		$this->session = $session;

		$ucid = $session->ucid;
		if(!$ucid) {
			throw new ApiException(ApiException::Remind, '请先登陆');
		}

		$user = Ucuser::from_cache($ucid);
		if(!$user) {
			throw new ApiException(ApiException::Error, '玩家未找到');
		}

		$this->user = $user;

		if($user->is_freeze) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}
	}
}