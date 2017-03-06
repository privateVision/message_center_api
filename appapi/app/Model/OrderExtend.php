<?php
namespace App\Model;

class OrderExtend extends Model
{
	protected $table = 'order_extend';
	protected $primaryKey = 'id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

	public function getIsNotifyAttribute() {
		return $this->attributes['is_notify'] == 1;
	}

	public function setIsNotifyAttribute($value) {
		$this->attributes['is_notify'] = $value ? 1 : 0;
	}
}