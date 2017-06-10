<?php
namespace App\Model;

class ProceduresExtend extends Model
{
	protected $table = 'procedures_extend';
	protected $primaryKey = 'pid';
	protected static $_instances = [];

	public function procedures() {
		return  $this->belongsTo(Procedures::class, 'pid', 'pid');
	}

    public function getThirdConfigAttribute() {
        $value = trim(@$this->attributes['third_config']);
        return json_decode($value, true) ?: [];
    }

	public function getBindPhoneNeedAttribute() {
		$value = trim(@$this->attributes['bind_phone_need']);
		return $value !== '' ? (bool)$value : true;
	}

	public function getBindPhoneEnforceAttribute() {
		$value = trim(@$this->attributes['bind_phone_enforce']);
		return $value !== '' ? (bool)$value : false;
	}

	public function getRealNameNeedAttribute() {
		$value = trim(@$this->attributes['real_name_need']);
		return $value !== '' ? (bool)$value : false;
	}

	public function getRealNameEnforceAttribute() {
		$value = trim(@$this->attributes['real_name_enforce']);
		return $value !== '' ? (bool)$value : false;
	}

	public function getServiceQqAttribute() {
		$value = trim(@$this->attributes['service_qq']);
		return $value !== '' ? $value : env('service_qq');
	}

	public function getServicePageAttribute() {
		$value = trim(@$this->attributes['service_page']);
		return $value !== '' ? $value : env('service_page');
	}

	public function getServicePhoneAttribute() {
		$value = trim(@$this->attributes['service_phone']);
		return $value !== '' ? $value : env('service_phone');
	}

	public function getServiceShareAttribute() {
		$value = trim(@$this->attributes['service_share']);
		return $value !== '' ? $value : env('service_share');
	}
/*
	public function getHeartbeatIntervalAttribute() {
		$value = trim(@$this->attributes['heartbeat_interval']);
		return is_numeric($value) ? intval($value) : 2000;
	}
*/
	public function getBindPhoneIntervalAttribute() {
		$value = trim(@$this->attributes['bind_phone_interval']);
		return is_numeric($value) ? intval($value) : 259200000;
	}

	public function getAllowNumAttribute() {
		$value = intval(@$this->attributes['allow_num']);
		return $value >= 1 ? $value : 1;
	}

	public function getLogoutImgAttribute() {
		$value = trim(@$this->attributes['logout_img']);
		return $value !== '' ? $value : env('logout_img');
	}

	public function getLogoutRedirectAttribute() {
		$value = trim(@$this->attributes['logout_redirect']);
		return $value !== '' ? $value : env('logout_redirect');
	}

	public function getLogoutInsideAttribute() {
		$value = trim(@$this->attributes['logout_inside']);
		return $value !== '' ? (bool)$value : true;
	}

    /**
     * 是否开启测试模式
     * @param $version
     * @return bool
     */
	public function isSandbox($version) {
        if(!$this->test_version) return false;
        $versions = explode('|', $this->test_version);
        foreach($versions as $v) {
            if(version_compare($v, $version, '=')) return true;
        }

        return false;
    }

    /**
     * 是否启用FB功能
     */
    public function isEnableFB() {
        return ($this->enable & (1 << 9)) != 1;
    }

    /**
     * 在切换到安锋支付时也启用官方支付
     */
    public function isTooUseIAP() {
        return ($this->enable & (1 << 7)) != 0;
    }

    /**
     * 是否启用官方支付
     * @return boolean
     */
    public function isIAP() {
        return ($this->enable & (1 << 8)) == 0;
    }
}