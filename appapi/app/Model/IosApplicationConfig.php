<?php
namespace App\Model;

class IosApplicationConfig extends Model {

    protected $table = 'ios_application_config';
    protected $primaryKey = 'app_id';
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

}
