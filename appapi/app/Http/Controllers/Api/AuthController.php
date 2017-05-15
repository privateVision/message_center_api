<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Session;
use App\Model\UcuserSession;
use App\Model\Ucuser;
use Illuminate\Http\Response;

class AuthController extends Controller {

	protected $user = null;
	protected $user_info = null;
	protected $session = null;

	public function before(Request $request) {
		parent::before($request);

		$token = $this->parameter->tough('_token');
		if(!$token) {
			throw new ApiException(ApiException::Expire, '请先登录');
		}

		$usession = UcuserSession::from_cache_session_token($token);
		if(!$usession) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登录');
		}

		$user = Ucuser::from_cache($usession->ucid);
		if(!$user) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登录');
		}

		$session = Session::find($token);
		if(!$session) {
			throw new ApiException(ApiException::Expire, '会话已失效，请重新登录');
		}

		if($user->is_freeze) {
			throw new ApiException(ApiException::AccountFreeze, '账号已被冻结');
		}

		$this->session = $session;
		$this->user = $user;
	}

	public function onResponse(Request $request, Response $response) {
	    if(!$response->exception) {
    	    $content = $response->getOriginalContent();
    	    $content['_token'] = $this->parameter->tough('_token');
    	    $response->setContent($content);
	    }
	    
	    return parent::onResponse($request, $response);
	}
}