<?php
namespace App\Model;

class Ucusers extends Model
{
    protected $table = 'ucusers';
    protected $primaryKey = 'ucid';

    protected $hidden = ['password'];


    public function  quick_name() {
        $username = printf("af%d",mt_rand(11111111, 99999999));
        $sql = "select uid from 56gamebbs.pre_ucenter_members where username='{$username}'";
        $result = DB::query($sql);
        return $result;
       //DB()->query($sql)
    }
}