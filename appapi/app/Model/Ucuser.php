<?php
namespace App\Model;
use App\Redis;

class Ucuser extends Model
{
    protected $table = 'ucusers';
    protected $primaryKey = 'ucid';

    const CREATED_AT = 'createTime';
    const UPDATED_AT = 'updated_at';

    const IsFreeze_Normal = 0;
    const IsFreeze_Freeze = 1;
    const IsFreeze_Abnormal = 2;

    public function orders() {
        return $this->belongsTo(Orders::class, 'ucid', 'ucid');
    }

    public function getIsFreezeAttribute() {
        return @$this->attributes['is_freeze'] == 1;
    }

    public function getBalanceAttribute() {
        $value = @$this->attributes['balance'];
        return sprintf('%.2f', $value ? $value : 0);
    }

    public function setPassword($password) {
        if(!isset($this->attributes['salt'])) {
            $this->attributes['salt'] = rand(100000, 999999);
        }

        $this->attributes['password'] = md5(md5($password) . $this->attributes['salt']);
    }
/*
    public function getMobile() {
        if(strlen((string)$this->moblile) !== 11) return '';
        return substr($this->moblile, 0, 3) .'*****'. substr($this->moblile, -4);
    }
*/
    /**
     * 验证登录密码
     * @param  [type] $password [description]
     * @return [type]           [description]
     */
    public function checkPassword($password) {
        return $this->password === md5(md5($password) . $this->salt);
    }
}