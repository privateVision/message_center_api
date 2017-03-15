<?php
namespace App\Model;

class UcuserProcedure extends ModelSection
{
    protected $table = 'ucuser_procedure';

    public function part($n) {
        return 0;//$n % 30;
    }

    public static function boot() {
        parent::boot();
    
        static::creating(function($entry) {
            $count = UcuserProcedure::section($entry->ucid)->where('ucid', $entry->ucid)->where('pid', $entry->pid)->count();
            $entry->name = sprintf('AF%09d_%02d', $entry->ucid, $count + 1);
        });
    }
}