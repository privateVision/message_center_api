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

 function sendrequest($callback,$ispost=false,$data= array()){

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
  }
//创建生成token
	function uuid() {
			return md5(uniqid() . rand(0, 999999));
	}
//用户密码格式化 明文pass 加密key

function getTypePass($pass,$key){
	return md5(md5($pass).$key);
}
