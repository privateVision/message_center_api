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
		$value = $this->attributes['bind_phone_need'];
		return ($value === null || $value === "") ? true : $value == 1;
	}

	public function getBindPhoneEnforceAttribute() {
		$value = $this->attributes['bind_phone_enforce'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getRealNameNeedAttribute() {
		$value = $this->attributes['real_name_need'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getRealNameEnforceAttribute() {
		$value = $this->attributes['real_name_enforce'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getServiceQqAttribute() {
		$value = $this->attributes['service_qq'];
		return $value ?: env('SERVICE_QQ');
	}

	public function getServicePageAttribute() {
		$value = $this->attributes['service_page'];
		return $value ?: env('SERVICE_PAGE');
	}

	public function getServicePhoneAttribute() {
		$value = $this->attributes['service_phone'];
		return $value ?: env('SERVICE_PHONE');
	}

	public function getServiceShareAttribute() {
		$value = $this->attributes['service_share'];
		return $value ?: env('SERVICE_SHARE');
	}

	public function getServiceIntervalAttribute() {
		$value = $this->attributes['service_interval'];
		return ($value === null || $value === "") ? 300 : $value == 1;
	}

	public function getBindPhoneIntervalAttribute() {
		$value = $this->attributes['bind_phone_interval'];
		return ($value === null || $value === "") ? 86400 : $value == 1;
	}

	public function getAllowNumAttribute() {
		$value = intval($this->attributes['allow_num']);
		return $value ?: 1;
	}
}