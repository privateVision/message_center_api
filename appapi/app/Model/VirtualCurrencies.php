<?php
namespace App\Model;

class VirtualCurrencies extends Model
{
    protected $table = 'virtualCurrencies';
    protected $primaryKey = 'vcid';

    /**
     * 检查储值卡是否可用
     * @param  [type]  $pid [description]
     * @return mixed false，不可用，0可用且不限制时间，大于0表示可用且限制的时间
     */
    public function is_valid($pid) {
        if($this->lockApp && $this->lockApp != $pid) return false;
        
        $e = 0;

        if(!$this->untimed) {
            $s = strtotime($this->startTime);
            $e = strtotime($this->endTime);
            $t = time();
            if($s > $e || $s > $t || $e < $t) {
                return false;
            }
        }

        return $e;
    }
}