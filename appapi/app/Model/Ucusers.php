<?php
namespace App\Model;

use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Retailers;

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

    public function vip() {
        // todo: 计算用户VIP
    }
}