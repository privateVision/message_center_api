<?php
namespace App\Model;

class UcuserSub extends Model
{
    protected $table = 'ucuser_sub';
    public $incrementing = false;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getTable() {
        $ucid = @$this->ucid ?: ($this->slice ?: 0);

        if($ucid) {
            return $this->table .'_'. 0;//($ucid / 500000);
        }

        return $this->table;
    }

    public function getIsFreezeAttribute() {
        return $this->attributes['is_freeze'] == 1;
    }
}