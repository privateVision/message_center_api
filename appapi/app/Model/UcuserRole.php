<?php
namespace App\Model;

class UcuserRole extends Model
{
    protected $table = 'ucuser_role';
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updateTime';

    public function getTable() {
        $pid = @$this->pid ?: ($this->slice ?: 0);

        if($pid) {
            return $this->table .'_'. $pid;
        }

        return $this->table;
    }
}