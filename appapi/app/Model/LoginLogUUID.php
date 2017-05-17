<?php
namespace App\Model;

class LoginLogUUID extends Model
{
	protected $table = 'login_log_uuid';
	protected $primaryKey = 'uuid';
	public $incrementing = false;
}