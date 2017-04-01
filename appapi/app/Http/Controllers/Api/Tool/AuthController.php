<?php // 所有继承此类的方法都需要传ucid
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Parameter;
use App\Model\Ucuser;

class AuthController extends Controller
{
    protected $user = null;

    public function before() {
        $ucid = $this->parameter->tough('ucid');
        
        $user = Ucuser::from_cache($ucid);
        if(!$user) {
            throw new ApiException(ApiException::Remind, '用户不存在');
        }

        $this->user = $user;
    }
}