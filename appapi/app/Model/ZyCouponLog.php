<?php
namespace App\Model;

class ZyCouponLog extends Model
{
    protected $table = 'zy_coupon_log';
    protected $primaryKey = 'id';

    protected $coupon = null;
}