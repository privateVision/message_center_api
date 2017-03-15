<?php
namespace App\Model;

class ProceduresExtend extends Model
{
	protected $table = 'procedures_extend';
	protected $primaryKey = 'pid';

	public function procedures() {
		return  $this->belongsTo(Procedures::class, 'pid', 'pid');
	}

	public function getBindPhoneNeedAttribute() {
		return $this->attributes['bind_phone_need'] == 1;
	}

	public function getBindPhoneEnforceAttribute() {
		return $this->attributes['bind_phone_enforce'] == 1 ? 1 : 0;
	}

	public function getRealNameNeedAttribute() {
		return $this->attributes['real_name_need'] == 1 ? 1 : 0;
	}

	public function getRealNameEnforceAttribute() {
		return $this->attributes['real_name_enforce'] == 1;
	}
}