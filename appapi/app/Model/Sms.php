<?php
namespace App\Model;

class SMS extends Model
{
	protected $table = 'sms';
	const CREATED_AT = 'sendTime';
	protected $primaryKey = "id";

	public function ucusers(){
		return $this->hasOne(Ucusers::class,'mobile',"mobile");
	}
}