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
	protected $session = null;

	public function before(Request $request) {
		parent::before($request);

		$token = $this->parameter->tough('_token');
		if(!$token) {
			throw new ApiException(ApiException::Expire, trans('messages.invalid_token'));
		}

		$usession = UcuserSession::where('session_token', $token)->first();
		if(!$usession) {
			throw new ApiException(ApiException::Expire, trans('messages.invalid_token'));
		}

		$user = Ucuser::find($usession->ucid);
		if(!$user) {
			throw new ApiException(ApiException::Expire, trans('messages.invalid_token'));
		}

		$session = Session::find($token);
		if(!$session) {
			throw new ApiException(ApiException::Expire, trans('messages.invalid_token'));
		}

		if($user->is_freeze == Ucuser::IsFreeze_Freeze) {
            throw new ApiException(ApiException::AccountFreeze, trans('messages.freeze'));
        }

        if($user->is_freeze == Ucuser::IsFreeze_Abnormal) {
            throw new ApiException(ApiException::AccountFreeze, trans('messages.abnormal'));
        }

		$this->session = $session;
		$this->user = $user;
	}

	public function onResponse(Request $request, Response $response) {
	    $content = $response->getOriginalContent();
	    
    	$content['_token'] = $this->parameter->tough('_token');
    	$response->setContent($content);
	    
	    return parent::onResponse($request, $response);
	}
}