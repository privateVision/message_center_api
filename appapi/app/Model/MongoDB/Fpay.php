<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/6
 * Time: 15:39
 */

namespace App\Model\MongoDB;
use App\Model\MongoDB\Model;

class Fpay extends Model{
    protected $collection = 'fpay_log';
    const CREATED_AT = null;
    const UPDATED_AT = null;

}
