<?php
function encrypt3des($data, $key = null) {
	if(empty($key)) {
		$key = env('3DES_KEY');
	}

	return \App\Crypt3DES::encrypt($data, $key);
}

function decrypt3des($data, $key = null) {
	if(empty($key)) {
		$key = env('3DES_KEY');
	}

	return \App\Crypt3DES::decrypt($data, $key);
}

function uuid() {
	return md5(uniqid() . rand(0, 999999));
}