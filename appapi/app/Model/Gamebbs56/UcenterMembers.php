<?php
namespace App\Model\Gamebbs56;

use App\Model\Ucusers;
use App\Model\UcusersExtend;

class UcenterMembers extends Model
{
    protected $table = "ucenter_members";
    protected $primaryKey = 'uid';

    public function ucusers() {
        return $this->hasOne(Ucusers::class, 'ucid', 'uid');
    }

    public function setPasswordAttribute($value) {
        $this->attributes['salt'] = rand(100000, 999999);
        $this->attributes['password'] = md5(md5($value) . $this->attributes['salt']);
    }

    public function checkPassword($password) {
        return $this->password === md5(md5($password) . $this->salt);
    }

    public function ucusers_extend(){
        return $this->hasOne(UcusersExtend::class,'uid','uid');
    }

}