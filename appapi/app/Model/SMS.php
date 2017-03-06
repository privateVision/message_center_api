<?php
namespace App\Model;

class SMS extends Model
{
    const CREATED_AT = 'sendTime';

	protected $table = 'sms';
	protected $primaryKey = "id";

	public function ucusers(){
		return $this->hasOne(Ucusers::class, 'mobile', "mobile");
	}
}