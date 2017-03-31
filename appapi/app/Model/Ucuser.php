<?php
namespace App\Model;

class Ucuser extends Model
{
    protected $table = 'ucusers';
    protected $primaryKey = 'ucid';
    //protected $hidden = ['password', 'salt'];

    const CREATED_AT = 'createTime';
    const UPDATED_AT = 'updated_at';

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

    public function setPasswordAttribute($value) {
        if(!isset($this->attributes['salt'])) {
            $this->attributes['salt'] = rand(100000, 999999);
        }
        $this->attributes['password'] = md5(md5($value) . $this->attributes['salt']);
    }

    /**
     * 验证登陆密码
     * @param  [type] $password [description]
     * @return [type]           [description]
     */
    public function checkPassword($password) {
        return $this->password === md5(md5($password) . $this->salt);
    }
}