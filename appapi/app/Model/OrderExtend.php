<?php
namespace App\Model;

class OrderExtend extends Model
{

	protected $table = 'order_extend';
	protected $primaryKey = 'oid';
	public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function setExtraParamsAttribute($value) {
        if(!is_array($value)) {
            $value = [$value];
        }

        $this->attributes['extra_params'] = json_encode(array_merge($this->extra_params , $value));
    }

    public function getExtraParamsAttribute($value) {
        if(@$this->attributes['extra_params']) {
            return json_decode($this->attributes['extra_params'], true) ?: [];
        }

        return [];
    }

    public function is_f() {
        return $this->product_type == 1;
    }
}