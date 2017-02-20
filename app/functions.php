<?php
function encrypt3des($data, $key = null) {
	if(empty($key)) {
		$key = env('API_3DES_KEY');
	}

	return \App\Crypt3DES::encrypt($data, $key);
}

function decrypt3des($data, $key = null) {
	if(empty($key)) {
		$key = env('API_3DES_KEY');
	}

	return \App\Crypt3DES::decrypt($data, $key);
}

<<<<<<< HEAD

function sendrequest($callback,$ispost=false,$data= array())
    {

        date_default_timezone_set('PRC');
        $curlobj = curl_init();         // 初始化
        curl_setopt($curlobj, CURLOPT_URL, $callback);      // 设置访问网页的URL
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, true);            // 执行之后不直接打印出来
        // Cookie相关设置，这部分设置需要在所有会话开始之前设置
        curl_setopt($curlobj, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        if($ispost){
            curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1); // 这样能够让cURL支持页面链接跳转
            curl_setopt($curlobj, CURLOPT_POST, 1);
            curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curlobj, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded; charset=utf-8"));
        $output=curl_exec($curlobj);    // 执行

        curl_close($curlobj);// 关闭cURL
        return $output;
       //  $d = json_decode($output);
         //echo ($d->ret == 0)?json_encode(array("code"=>1,"msg"=>"成功")):json_encode(array("code"=>0,"msg"=>"发送失败"));
        // echo $output; //curl的方式获取当前的页面的信息
    }

		function uuid() {
  			return md5(uniqid() . rand(0, 999999));
		}
=======
function uuid() {
	return md5(uniqid() . rand(0, 999999));
}
>>>>>>> d003d56051f534bc582bb2020c6a3b0438eea1c3
