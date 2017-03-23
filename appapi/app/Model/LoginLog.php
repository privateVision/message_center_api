<?php
namespace App\Model;

use App\Model\MongoDB\AppVipRules;

class LoginLog extends Model
{
    protected $table = 'login_log';
    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}