<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
	protected $connection = 'anfanapi';

	const CREATED_AT = null;
    const UPDATED_AT = null;
}