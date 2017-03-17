<?php
namespace App\Model;

class SMSRecord extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

	protected $table = 'sms_record';
}