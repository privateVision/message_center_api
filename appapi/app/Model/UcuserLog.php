<?php
namespace App\Model;

class UcuserLog extends Model
{
    protected $table = 'ucuser_log';
    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}