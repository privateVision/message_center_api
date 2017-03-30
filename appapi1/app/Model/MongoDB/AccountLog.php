<?php
namespace App\Model\MongoDB;

use App\Model\MongoDB\Model;

class AccountLog extends Model{

    protected $collection = "sdk_api_account_log";
    const CREATED_AT = null;
    const UPDATED_AT = null;
}