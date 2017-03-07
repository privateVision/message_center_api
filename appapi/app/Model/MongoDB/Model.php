<?php
namespace App\Model\MongoDB;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
	protected $connection = 'mongodb';
}