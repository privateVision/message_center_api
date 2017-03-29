<?php
namespace App\Model;

class VirtualCurrencies extends Model
{
    protected $table = 'virtualCurrencies';
    protected $primaryKey = 'vcid';

    public function is_valid($pid) {
        if($this->lockApp && $this->lockApp != $pid) return false;
        
        if(!$this->untimed) {
            $s = strtotime($this->startTime);
            $e = strtotime($this->endTime);
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        return true;
    }
}