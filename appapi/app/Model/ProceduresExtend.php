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
		$value = @$this->attributes['bind_phone_need'];
		return ($value === null || $value === "") ? true : $value == 1;
	}

	public function getBindPhoneEnforceAttribute() {
		$value = @$this->attributes['bind_phone_enforce'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getRealNameNeedAttribute() {
		$value = @$this->attributes['real_name_need'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getRealNameEnforceAttribute() {
		$value = @$this->attributes['real_name_enforce'];
		return ($value === null || $value === "") ? false : $value == 1;
	}

	public function getServiceQqAttribute() {
		$value = @$this->attributes['service_qq'];
		return $value ?: env('SERVICE_QQ');
	}

	public function getServicePageAttribute() {
		$value = @$this->attributes['service_page'];
		return $value ?: env('SERVICE_PAGE');
	}

	public function getServicePhoneAttribute() {
		$value = @$this->attributes['service_phone'];
		return $value ?: env('SERVICE_PHONE');
	}

	public function getServiceShareAttribute() {
		$value = @$this->attributes['service_share'];
		return $value ?: env('SERVICE_SHARE');
	}

	public function getHeartbeatIntervalAttribute() {
		$value = @$this->attributes['heartbeat_interval'];
		return ($value === null || $value === "") ? intval(env('default_heartbeat_interval')) : intval($value);
	}

	public function getBindPhoneIntervalAttribute() {
		$value = @$this->attributes['bind_phone_interval'];
		return ($value === null || $value === "") ? 86400000 : $value;
	}

	public function getAllowNumAttribute() {
		$value = intval(@$this->attributes['allow_num']);
		return $value ?: 1;
	}

	public function getLogoutImgAttribute() {
		$value = @$this->attributes['logout_img'];
		return $value ?: env('logout_img');
	}

	public function getLogoutTypeAttribute() {
		$logout_img = @$this->attributes['logout_img'];
		$logout_type = @$this->attributes['logout_type'];
		return $logout_img && $logout_type ? $logout_type : env('logout_type');
	}

	public function getLogoutRedirectAttribute() {
		$logout_img = @$this->attributes['logout_img'];
		$logout_type = @$this->attributes['logout_type'];
		$logout_redirect = @$this->attributes['logout_redirect'];
		return $logout_img && $logout_redirect ? $logout_redirect : env('logout_redirect');
	}

	public function getLogoutInsideAttribute() {
		$value = @$this->attributes['logout_inside'];
		return $value ? true : env('logout_inside');
	}
}