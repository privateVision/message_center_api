<?php
namespace App\Model;

use App\Model\MongoDB\AppVipRules;

class User extends Model
{
    protected $table = 'user_log';
    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}