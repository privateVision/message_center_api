<?php
namespace App\Model;

class UcuserProcedure extends ModelSection
{
    protected $table = 'ucuser_procedure';

    public function part($n) {
        return $n % 100;
    }
}