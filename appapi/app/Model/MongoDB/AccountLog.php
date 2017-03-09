<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/7
 * Time: 14:17
 */

namespace App\Model\MongoDB;

use App\Model\MongoDB\Model;

class AccountLog extends Model{
    protected $connection = "sdk_api_account_log";
    const CREATED_AT = null;
    const UPDATED_AT = null;

}