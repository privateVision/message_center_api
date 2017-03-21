<?php
namespace App\Model;

class UserSub extends Model
{
    protected $table = 'user_sub';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getTable() {
        $ucid = @$this->ucid ?: ($this->slice ?: 0);

        if($ucid) {
            return $this->table .'_'. 0;//($ucid / 500000);
        }

        return $this->table;
    }
}