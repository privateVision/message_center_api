<?php
namespace App\Model;

class Orders extends Model
{
	const Status_WaitPay = 0;
	const Status_Success = 2;
	const Status_NotifySuccess = 1;

	const Way_Unknow = 0;
	const Way_Wechat = 1;
	const Way_Alipay = 2;
	const Way_UnionPay = 3;

	protected $table = 'orders';
	protected $primaryKey = 'id';
	//protected $fillable = ['hide'];

	const CREATED_AT = 'createTime';

	public function user() {
		return $this->belongsTo(Ucuser::class, 'ucid', 'ucid');
	}

	public function procedures() {
		return $this->hasOne(Procedures::class, 'pid', 'vid');
	}

	public function order_extend() {
		return $this->hasOne(OrderExtend::class, 'order_id', 'id');
	}

	public function ordersExt() {
		return $this->hasMany(OrdersExt::class, 'oid', 'id');
	}

	public function getHideAttribute() {
		return $this->attributes['hide'] == 1;
	}

	public function setHideAttribute($value) {
		$this->attributes['hide'] = $value ? 1 : 0;
	}

	public function ios_order_ext(){
		return $this->belongsTo(IosOrderExt::class,'oid','id');
	}

	public function is_first() {
		// todo: 单独字段标识，这太Low了
		return static::where('ucid', $this->ucid)->where('status', '!=', static::Status_WaitPay)->where("vid", $this->vid)->count() == 0;
	}

	/**
	 * 该订单是否是购买F币，如果orders_extend存在则以product_type为准，否则才走这里
	 * @return boolean [description]
	 */
	public function is_f() {
		return $this->vid < 100 || empty($this->notify_url);
	}
/*
	public static function whereIsF() {
		return static::where(function($query) {
			$query->where('vid', '<', 100);
		});
	}

	public static function whereIsNotF() {
		return static::where(function($query) {
			$query->where('vid', '>=', 100);
		});
	}
*/
}