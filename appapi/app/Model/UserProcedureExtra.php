<?php
namespace App\Model;

class UserProcedureExtra extends Model
{
    const Status_Normal = 0; // 正常
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'user_procedure_extra';
}