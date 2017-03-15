<?php
namespace App\Model;

use App\Model\Gamebbs56\UcenterMembers;
use App\Model\MongoDB\AppVipRules;
use App\Model\MongoDB\User;

class Ucusers extends Model
{
    protected $table = 'ucusers';
    protected $primaryKey = 'ucid';
    public $incrementing = false;
    protected $fillable = ['ucid', 'uid', 'mobile', 'balance', 'uuid', 'rid', 'pid', 'subRetailer'];
    protected $hidden = ['password'];

    const CREATED_AT = 'createTime';

    public function orders() {
        return $this->belongsTo(Orders::class, 'ucid', 'ucid');
    }

    public function ucenter_members() {
        return $this->belongsTo(UcenterMembers::class, 'ucid', 'uid');
    }

    public function retailers() {
        return $this->hasOne(Retailers::class, 'rid', 'rid');
    }

    public function ucuser_total_pay() {
        return $this->hasOne(UcuserTotalPay::class, 'ucid', 'ucid');
    }

    public function getBalanceAttribute($value) {
        return number_format($value, 2);
    }

    /**
     * 验证登陆密码
     * @param  [type] $password [description]
     * @return [type]           [description]
     */
    public function checkPassword($password) {
        if(!$this->ucenter_members) return false;
        return $this->ucenter_members->password === md5(md5($password) . $this->ucenter_members->salt);
    }

    /*
     * @doc设置新的用户密码
     * @return 新生成的用户的明文信息
     * */

    public function setNewPassword($password = null) {
        if($password == null) {
            $password = rand(111111, 999999);
        }

        $this->ucenter_members->password = $password;
        $this->ucenter_members->save();

        return $password;
    }

    /*
     * 设置用户名
     * */
    public function setUcname($name){
        $this->ucenter_members->username = $name;
        $this->ucenter_members->save();
    }

    /**
     * 用户是否被冻结
     * @return boolean
     */
    public function isFreeze() {
        return $this->ucusers_extend ? $this->ucusers_extend->isfreeze : false;
    }

    /**
     * 用户是否实名制
     * @return boolean
     */
    public function isReal() {
        return $this->ucusers_extend ? $this->ucusers_extend->is_real : false;
    }

    /**
     * 用户是否成人
     * @return boolean
     */
    public function isAdult() {
        if($this->ucusers_extend && $this->ucusers_extend->card_id) {
            $card = $this->ucusers_extend->card_id;
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