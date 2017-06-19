<?php

namespace  App\Model;

class UcuserLogin extends Model{

    protected $table = 'ucuser_login';
    protected $primaryKey = 'ucid';
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
