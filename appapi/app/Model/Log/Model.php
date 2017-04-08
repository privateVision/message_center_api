<?php
namespace App\Model\Log;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    protected $connection = 'log';
}