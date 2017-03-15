<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract  class ModelSection extends Eloquent
{
    public static function section($section, array $attributes = []) {
        $instance = new static($attributes);
        
        $part = $instance->part($section);
        $table = $instance->getTable() . '_' . $part;
        $instance->setTable($table); 

        return $instance;
    }

    abstract public function part($section);
}