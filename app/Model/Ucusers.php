<?php
namespace App\Model;

use App\Model\Gamebbs56\UcenterMembers;
use App\Model\Retailers;

class Ucusers extends Model
{
    protected $table = 'ucusers';
    protected $primaryKey = 'ucid';

    protected $hidden = ['password'];

    public function ucenter_members() {
        return $this->hasOne(UcenterMembers::class, 'uid', 'ucid');
    }

    public function retailers() {
        return $this->hasOne(Retailers::class, 'rid', 'rid');
    }

    public function vip() {
        // todo: 计算用户VIP
    }
}