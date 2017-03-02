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

    public function ucenter_members() {
        return $this->belongsTo(UcenterMembers::class, 'ucid', 'uid');
    }

    public function retailers() {
        return $this->hasOne(Retailers::class, 'rid', 'rid');
    }

    public function ucuser_total_pay() {
        return $this->hasOne(UcuserTotalPay::class, 'ucid', 'ucid');
    }

    //关联到 ucusers_extend
    public function ucusers_extend(){
        return $this->hasOne(UcusersExtend::class,'ucid','uid');
    }

    public function getBalanceAttribute($value) {
        return number_format($value, 2);
    }

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

    public function coupon() {
        return [];
    }
}