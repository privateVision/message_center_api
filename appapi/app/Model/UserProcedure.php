<?php
namespace App\Model;

class UserProcedure extends Model
{
    protected $table = 'user_procedure';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getTable() {
        $id = @$this->id ?: @$this->slice['id'];
        $ucid = @$this->ucid ?: (@$this->slice['ucid'] ?: $this->slice);

        if($ucid) {
            return $this->table .'_'. 0;//$ucid % 30;
        } else if($id) {
            return $this->table .'_'. 0;//int(($id - 1) / 71582788);
        }

        return $this->table;
    }
}