<?php

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Session;
use App\Model\Users;

class AuthController extends Controller {

	private $user = null;

	public function before(Request $request, Parameter $parameter) {
		parent::before($request, $parameter);

		$uid = $this->getSession()->uid;
		if(!$uid) {
			throw new ApiException(ApiException::Remind, '请先登陆');
		}

		$user = Users::find($uid);
		if(!$user) {
			throw new ApiException(ApiException::Error, '玩家未找到');
		}

		$this->user = $user;
	}

	protected function getUser() {
		return $this->user;
	}
}