<?php
namespace App\Model;

class UcuserSubService extends Model
{
    const Status_Normal = 0; // 正常

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'ucuser_sub_service';
    
}