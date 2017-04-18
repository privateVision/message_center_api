<?php
namespace App\Model;

class ProceduresZone extends Model
{
	protected $table = 'procedures_zone';
	protected $primaryKey = 'id';

	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}