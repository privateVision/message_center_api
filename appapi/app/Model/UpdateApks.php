<?php
namespace App\Model;

class UpdateApks extends Model
{
    protected $table = 'update_apks';
    protected $primaryKey = 'pid';

    public function procedures() {
        return $this->belongsTo(Procedures::class, 'pid', 'pid');
    }

    public function getForceUpdateAttribute() {
        return $this->attributes['force_update'] == 1;
    }
}