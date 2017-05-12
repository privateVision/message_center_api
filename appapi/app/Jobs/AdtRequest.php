<?php
namespace App\Jobs;
use App\Crypt3DES;
class AdtRequest extends Job
{
  
    private  $url  = "";
    private  $data = array();
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {   
             $des = "f69fc0e2575511a7f69fc0e2";
            print_r($this->data);
           // $this->sendrequest($this->url,True,["key"=>Crypt3DES::encrypt($this->data,$des)]); //请求地址
    }



}