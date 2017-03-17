<?php
namespace App\Model;

use App\Model\MongoDB\AppVipRules;

class User extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'ucid';
    protected $hidden = ['password', 'salt'];

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

    public function getIsFreezeAttribute() {
        return $this->attributes['is_freeze'] == 1;
    }

    public function setIsFreezeAttribute($value) {
        $this->attributes['is_freeze'] = $value ? 1 : 0;
    }

    public function getBalanceAttribute($value) {
        return number_format($value, 2);
    }

    public function setPasswordAttribute($value) {
        $this->attributes['salt'] = rand(100000, 999999);
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
        if($this->card_id) {
            $card = $this->card_id;
            $y = substr($card, 6, 4);
            $m = substr($card, 10, 2);
            $d = substr($card, 12, 2);

            $interval = date_diff(date_create("{$y}-{$m}-{$d}"), date_create(date('Y-m-d')));

            return $interval && @$interval['y'] >= 18;
        }

        return false;
    }

    /**
     * 用户VIP等级
     * @return int
     */
    public function vip() {
        $ucuser_total_pay = $this->ucuser_total_pay;

        $level = 0;

        if($ucuser_total_pay) {
            $pay_fee = $ucuser_total_pay->pay_fee;
           

            $rules = AppVipRules::orderBy('fee', 1)->get();

            foreach($rules as $k => $v) {
                if($pay_fee >= $v->fee) {
                    $level = $k;
                } else {
                    break;
                }
            }
        }

        return $level;
    }

    /**
     * 用户卡券列表
     * @return array
     */
    public function coupon() {
        return [];
    }
}