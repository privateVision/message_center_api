<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class PartModel extends Eloquent
{
    protected $section = null;

    public static function part($section, array $attributes = []) {
        $instance = new static($attributes);
        $instance->section = $section;
        return $instance;
    }
}