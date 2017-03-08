<?php
namespace App\Model;

class Session extends Model
{
	// 前3位有1位为1就表示IOS
	const DevicePlatform_IOSPhone		= 0b00000001; // 1
	const DevicePlatform_IOSPad			= 0b00000010; // 2
	const DevicePlatform_IOSTV 			= 0b00000100; // 4
	// 4~6位有1位为1就表示android
	const DevicePlatform_AndroidPhone	= 0b00001000; // 8
	const DevicePlatform_AndroidPad		= 0b00010000; // 16
	const DevicePlatform_AndroidTV		= 0b00100000; // 32
	// 7~8位表示其它
	const DevicePlatform_PC 			= 0b01000000; // 64
	const DevicePlatform_Web 			= 0b10000000; // 128

	protected $table = 'session';

	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getIsServiceLoginAttribute() {
		return $this->attributes['is_service_login'] == 1;
	}

	public function setIsServiceLoginAttribute($value) {
		$this->attributes['is_service_login'] = $value ? 1 : 0;
	}
}