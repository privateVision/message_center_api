<?php

namespace App\Model;

class IP2Location extends Model
{
    protected $table = 'ip2location';
    protected $primaryKey = 'ip';
    public $incrementing = false;
}