<?php
namespace App\Model;

class Orders extends Model
{
	const Status_WaitPay = 0;
	const Status_Success = 1;

	const Way_Unknow = 0;
	const Way_Wechat = 1;
	const Way_Alipay = 2;
	const Way_UnionPay = 3;

	protected $table = 'orders';
	protected $primaryKey = 'id';

	const CREATED_AT = 'createTime';

	public function getHideAttribute() {
		return $this->attributes['hide'] == 1;
	}

	public function setHideAttribute($value) {
		$this->attributes['hide'] = $value ? 1 : 0;
	}
}