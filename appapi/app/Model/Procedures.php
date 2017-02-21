<?php
namespace App\Model;

class Procedures extends Model
{
	protected $table = 'procedures';
	protected $primaryKey = 'pid';

	public function procedures_extend() {
		return $this->hasOne(ProceduresExtend::class, 'pid', 'pid');
	}

	public function update_apks() {
		return $this->hasMany(UpdateApks::class, 'pid', 'pid');
	}

	public function deskey() {
		$md5 = md5($this->priKey);
		$a = substr($md5, 0, 16);
		$b = substr($md5, 0, 8);
		return $a . $b;
	}
}