<?php
namespace App\Model;

class UcuserProcedure extends ModelSection
{
    protected $table = 'ucuser_procedure';

    public function part($section) {
        if(is_numeric($section)) {
            return 0;//$section % 30;
        }

        if(isset($section['ucid'])) {
            return 0;//$section['ucid'] % 30;
        } else if(isset($section['id'])) {
            return intval(($section['id'] - 1) / 71582788);
        }
    }

    public static function boot() {
        parent::boot();
    
        static::creating(function($entry) {
            $count = UcuserProcedure::section($entry->ucid)->where('ucid', $entry->ucid)->where('pid', $entry->pid)->count();
            $entry->name = sprintf('AF%09d_%02d', $entry->ucid, $count + 1);
        });
    }
}