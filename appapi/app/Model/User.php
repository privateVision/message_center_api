<?php
namespace App\Model;

class User extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'ucid';
    protected $hidden = ['password', 'salt'];
    protected $casts = [
        'ucid' => 'integer',
        'uid' => 'string',
        'nickname' => 'string',
        'mobile' => 'string',
        'email' => 'string',
        'gender' => 'integer',
        'balance' => 'float',
        'birthday' => 'string',
        'address' => 'string',
        'avatar' => 'string',
        'real_name' => 'string',
        'card_id' => 'string',
        'is_freeze' => 'boolean',
        'exp' => 'integer',
        'vip' => 'integer',
        'score' => 'integer',
    ];

    const CREATED_AT = 'createTime';
    const UPDATED_AT = 'updated_at';

    public function orders() {
        return $this->belongsTo(Orders::class, 'ucid', 'ucid');
    }

    public function retailers() {
        return $this->hasOne(Retailers::class, 'rid', 'rid');
    }

    public function ucuser_total_pay() {
        return $this->hasOne(UcuserTotalPay::class, 'ucid', 'ucid');
    }

    public function ucusers_extend() {
        return $this->hasOne(UcusersExtend::class, 'ucid', 'ucid');
    }

    public function user_oauth() {
        return $this->hasOne(UserOauth::class, 'ucid', 'ucid');
    }

    public function getAvatarAttribute() {
        $avatar = @$this->attributes['avatar'];
        return $avatar ?: env('default_avatar');
    }

    public function getIsFreezeAttribute() {
        return @$this->attributes['is_freeze'] == 1;
    }

    public function getVipAttribute() {
        return intval(@$this->attributes['vip']);
    }

    public function setIsFreezeAttribute($value) {
        $this->attributes['is_freeze'] = $value ? 1 : 0;
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

    /**
     * 用户是否实名制
     * @return boolean
     */
    public function isReal() {
        return $this->card_id ? true : false;
    }

    /**
     * 用户是否成人
     * @return boolean
     */
    public function isAdult() {
        if($this->birthday && strlen($this->birthday) == 8) {
            $y = substr($this->birthday, 0, 4);
            $m = substr($this->birthday, 4, 2);
            $d = substr($this->birthday, 6, 2);

            $interval = date_diff(date_create("{$y}-{$m}-{$d}"), date_create(date('Y-m-d')));

            return $interval && @$interval->y >= 18;
        }

        return false;
    }
}