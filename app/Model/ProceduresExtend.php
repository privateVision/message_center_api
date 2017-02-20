<?php
namespace App\Model;

class ProceduresExtend extends Model
{
	protected $table = 'procedures_extend';
	protected $primaryKey = 'pid';

	public function procedure() {
		return  $this->belongsTo(Procedures::class, 'pid', 'pid');
	}

	public getBindPhoneNeedAttribute() {
		return $this->attributes['bind_phone_need'] == 1;
	}

	public getBindPhoneEnforceAttribute() {
		return $this->attributes['bind_phone_enforce'] == 1;
	}

	public getRealNameNeedAttribute() {
		return $this->attributes['real_name_need'] == 1;
	}

	public getRealNameEnforceAttribute() {
		return $this->attributes['real_name_enforce'] == 1;
	}
}