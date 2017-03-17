<?php
namespace App\Model;

class UserProcedure extends PartModel
{
    protected $table = 'user_procedure';

    public function getTable() {
        $id = @$this->id ?: @$this->section['id'];
        $ucid = @$this->ucid ?: (@$this->section['ucid'] ?: $this->section);

        if($ucid) {
            return $this->table .'_'. 0;//$ucid % 30;
        } else if($id) {
            return $this->table .'_'. int(($id - 1) / 71582788);
        }

        return $this->table;
    }

    public static function boot() {
        parent::boot();
    
        static::creating(function($entry) {
            $count = UserProcedure::part($entry->ucid)->where('ucid', $entry->ucid)->where('pid', $entry->pid)->count();
            $entry->name = sprintf('AF%09d_%02d', $entry->ucid, $count + 1);
        });
    }
}