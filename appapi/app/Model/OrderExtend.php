<?php
namespace App\Model;

class OrderExtend extends Model
{

	protected $table = 'order_extend';
	protected $primaryKey = 'oid';
	public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}