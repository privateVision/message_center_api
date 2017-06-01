<?php
namespace App\Model;

class TotalFeePerUser extends Model
{
    protected $table = 'total_fee_per_user';
    protected $primaryKey = 'ucid';
    public $incrementing = false;
}