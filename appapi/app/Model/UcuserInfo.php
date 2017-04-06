<?php
namespace App\Model;

class UcuserInfo extends Model
{
    protected $table = 'ucuser_info';
    protected $primaryKey = 'ucid';
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * 用户是否实名制
     * @return boolean
     */
    public function isReal() {
        return $this->card_no ? true : false;
    }

    /**
     * 用户是否成人
     * @return boolean
     */
    public function isAdult() {
        if($this->birthday && strlen($this->birthday) == 8) {
            $y = substr($this->birthday, 0, 4);
            $m = substr($this->birthday, 4, 2);
            $d = substr($this->birthday, 6, 2);

            $interval = date_diff(date_create("{$y}-{$m}-{$d}"), date_create(date('Y-m-d')));

            return $interval && @$interval->y >= 18;
        }

        return false;
    }
}