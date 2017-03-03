<<<<<<< HEAD
<?php
namespace App\Model;

class UcuserTotalPay extends Model
{
    protected $table = 'ucuser_total_pay';
    protected $primaryKey = 'ucid';
    protected $fillable = ['pay_count', 'pay_total', 'pay_fee'];
    public $incrementing = false;
=======
<?php
namespace App\Model;

class UcuserTotalPay extends Model
{
    protected $table = 'ucuser_total_pay';
    protected $primaryKey = 'ucid';
    protected $fillable = ['pay_count', 'pay_total', 'pay_fee'];
    public $incrementing = false;
>>>>>>> mllsdk
}