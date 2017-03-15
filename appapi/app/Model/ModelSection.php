<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract  class ModelSection extends Eloquent
{
    public static function section($n, array $attributes = []) {
        $instance = new static($attributes);
        $section = $instance->part($n);
        $table = $instance->getTable() . '_' . $section;
        $instance->setTable($table); 

        return $instance;
    }

    abstract public function part($n);
}