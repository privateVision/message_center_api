<<<<<<< HEAD
<?php
namespace App\Model;

class OrderExtend extends Model
{
	protected $table = 'order_extend';
	protected $primaryKey = 'order_id';

	public function getIsNotifyAttribute() {
		return $this->attributes['is_notify'] == 1;
	}

	public function setIsNotifyAttribute($value) {
		$this->attributes['is_notify'] = $value ? 1 : 0;
	}
=======
<?php
namespace App\Model;

class OrderExtend extends Model
{
	protected $table = 'order_extend';
	protected $primaryKey = 'order_id';

	public function getIsNotifyAttribute() {
		return $this->attributes['is_notify'] == 1;
	}

	public function setIsNotifyAttribute($value) {
		$this->attributes['is_notify'] = $value ? 1 : 0;
	}
>>>>>>> mllsdk
}