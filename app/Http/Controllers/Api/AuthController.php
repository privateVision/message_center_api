<?php

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\Ucusers;

class AuthController extends BaseController {

	protected $ucuser = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);

		$ucid = $this->session->ucid;
		if(!$ucid) {
			throw new ApiException(ApiException::Remind, '请先登陆');
		}

		$ucuser = Ucusers::find($ucid);
		if(!$ucuser) {
			throw new ApiException(ApiException::Error, '玩家未找到');
		}

		$this->ucuser = $ucuser;
	}
}