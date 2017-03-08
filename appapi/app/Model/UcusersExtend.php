<?php
namespace App\Model;

class UcusersExtend extends Model
{
    protected $table = 'ucusers_extend';
    protected $primaryKey = 'ucid';
    public $incrementing = false;

    public function getIsfreezeAttribute() {
        return $this->attributes['isfreeze'] == 1;
    }

    public function setIsfreezeAttribute($value) {
        $this->attributes['isfreeze'] = $value ? 1 : 0;
    }
}