<?php
namespace App\Model;

class Ucusers extends Model
{
<<<<<<< HEAD
	protected $table = 'users';
	protected $primaryKey = 'uid';


            public function  quick_name(){

                    $username = printf("af%d",mt_rand(11111111,99999999));

                    $sql = "select uid from 56gamebbs.pre_ucenter_members where username='{$username}'";
                    $result = DB::query($sql);
                    return $result;
                   //DB()->query($sql)
            }



=======
	protected $table = 'ucusers';
	protected $primaryKey = 'ucid';
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
}