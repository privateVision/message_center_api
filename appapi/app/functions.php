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

//创建生成token
function uuid() {
	return md5(uniqid() . rand(0, 999999));
}

/*
//用户密码格式化 明文pass 加密key
function getTypePass($pass,$key){
    return md5(md5($pass).$key);
}

//检测手机的格式
function checkMobile($mobile)
{
	return preg_match('/^(13|14|15|17|18)[0-9]{9}$/i', $mobile);
}

//检查当前用户名
function checkName($name)
{
	return preg_match(' /^[\w\_\-\.\@\:]+$/', $name);
}
*/
