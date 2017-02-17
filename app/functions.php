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