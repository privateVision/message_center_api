<?php 

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
//$app = require __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

//$app->run();
//
    class Es{
      public $key = "4c6e0a99384aff934c6e0a99";
        function encrypt($input) { // 数据加密
            $size = mcrypt_get_block_size ( MCRYPT_3DES, 'ecb' );
            $input = $this->pkcs5_pad ( $input, $size );
            $key = str_pad ( $this->key, 24, '0' );
            $td = mcrypt_module_open ( MCRYPT_3DES, '', 'ecb', '' );
            $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
            @mcrypt_generic_init ( $td, $key, $iv );
            $data = mcrypt_generic ( $td, $input );
            mcrypt_generic_deinit ( $td );
            mcrypt_module_close ( $td );
            $data = base64_encode ( $data );
            return $data;
        }

        function decrypt($encrypted) { // 数据解密
            $encrypted = base64_decode ( $encrypted );
            $key = str_pad ( $this->key, 24, '0' );
            $td = mcrypt_module_open ( MCRYPT_3DES, '', 'ecb', '' );
            $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
            $ks = mcrypt_enc_get_key_size ( $td );
            @mcrypt_generic_init ( $td, $key, $iv );
            $decrypted = mdecrypt_generic ( $td, $encrypted );
            mcrypt_generic_deinit ( $td );
            mcrypt_module_close ( $td );
            $y = $this->pkcs5_unpad ( $decrypted );
            return $y;
        }

        function pkcs5_pad($text, $blocksize) {
            $pad = $blocksize - (strlen ( $text ) % $blocksize);
            return $text . str_repeat ( chr ( $pad ), $pad );
        }

        function pkcs5_unpad($text) {
            $pad = ord ( $text {strlen ( $text ) - 1} );
            if ($pad > strlen ( $text )) {
                return false;
            }
            if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad) {
                return false;
            }
            return substr ( $text, 0, - 1 * $pad );
        }

        function PaddingPKCS7($data) {
            $block_size = mcrypt_get_block_size ( MCRYPT_3DES, MCRYPT_MODE_CBC );
            $padding_char = $block_size - (strlen ( $data ) % $block_size);
            $data .= str_repeat ( chr ( $padding_char ), $padding_char );
            return $data;
        }
    }

    $es = new Es();
    $ps = "XPTBBCTLOtiA49Ts/2LtDIAztnL2jivb7+il/ufH1ee4MgrHZphhEkNuHPJwzgzb4yFp5BmlmNIDrvQfSsNdUA==";

    $mk = [
    "kefu"=>"pSpZldso+HD4L2iAmdoDHKVC0lNmKT2ZzNJjzSfDfQ39jNKRbWvOtCIKr0dfxSnABpQdSpZWOldjVJxr367rfKymmd8lg6oBkDyoNmKIyljhphBkgvSeZD2WRZwScRNZGgAl344eJpSyEIJkBczqPA3SvX7zmqrXJFnkiojTzt4=",

    "update_game" =>"5DPzCzjI2rUVm4hOv1MNgIBJ5dnF4+AS",

     "login_token" =>"uIWcxy6XfwlEQTYJa7cl3/J1lwh7E6uasUucJz6Q8tt2E1estkkw4emYu3/NXn7T4yFp5BmlmNIDrvQfSsNdUA==",

     "broadcast" =>"K+AmiLKQykh93ZwmJ9jqs223P5IWLUdBFJOwebWw9AU9HMLsAxqj4PW3sEHZOd/OO65ogf3XAJM=",

     "annoucement" =>"K+AmiLKQykh93ZwmJ9jqs223P5IWLUdBFJOwebWw9AU9HMLsAxqj4PW3sEHZOd/OO65ogf3XAJM= ",

     "message_count"  => "000hmEbuqZRkaQtDYYHWyNIDN+OtB4ASHU3IlCwbW8J8ryu20YOdBncbf9WePfUhx57z0JhpyPO9Dp+bLC2NRoWWAD2f1rXe",

     "role_set" =>"K+AmiLKQykiLYpX5wyyTScApgKqFxdvKM/29+Thh3iHQnHXvUWWooaHCXASr1vD8nkQmSTDNi5oRjrNjcTk+BpwTJbsjrSlZX+hArjticl6nNDxn4a0rmQS0EZRx6YKvDqTE2+GAI3lQicvc3pWbkh9u6KN3b/1jII2wM9QKe0NTFQ/MPoY8VTuy49n1G6hUwQxGRb20DhMdZzDptKBplStpADf2adPWuIWVdz1sJrDx5A6EB9C7OQ==",

     "wallet" =>"K+AmiLKQykh2d6wlaNc9raw5cu6wY6rWagGL6Wp5B0+GBUW6H1ARrVOShhtqmQhJQKw94DgEVWRIhcjmGAv5gWEhsS9xujDPFnfZkj/UpoM=",

     "messages" =>"K+AmiLKQykiiWxytVUtH1Cw/qgXHsWmbbbc/khYtR0EUk7B5tbD0BT0cwuwDGqPg9bewQdk53847rmiB/dcAkw==" ,

     "anfan_v3" =>"K+AmiLKQykh1SX6S9hbMeZsIpJo2K9h0SgZgkPGQjEQYU/tiD4md+o2em4Vp6i2+hgVFuh9QEa1TkoYbapkISUCsPeA4BFVkSIXI5hgL+YFhIbEvcbowzxZ32ZI/1KaD",

     "pay_v3" => "K+AmiLKQykh1SX6S9hbMeZsIpJo2K9h0SgZgkPGQjEQYU/tiD4md+o2em4Vp6i2+hgVFuh9QEa1TkoYbapkISUCsPeA4BFVkSIXI5hgL+YFhIbEvcbowzxZ32ZI/1KaD",

     "unbind_authCode" =>"K+AmiLKQykgKFo/+vvH0SJv2RgS8STiqchd+743lI4KGBUW6H1ARrVOShhtqmQhJQKw94DgEVWRIhcjmGAv5gWEhsS9xujDPFnfZkj/UpoM=",
	 
	 "order_create"  =>"CAZMGEssE01bE4jTVvDHnIed9JwZmamJS0wFe824JfCD4y/R5qTeRaXlPTYFNyLGNuSk4N94JcqvWdswTCsbUwKGrLp6e5u8K2rs89c1oz1xIhdd1LSzT6MtH8bSC5CLHmuZJaXHmbPGuQOVggD4s/eEe+w3ishJb7ewX0mJgri1TfRAChaB/ufIzF5AiWcBMi/W6cEseuc/T9ySkm3mhdGxikDN5K7AeNhDRbxWoJLah//gyQak8h03xhKF6EzFYokBxQXAkWASI9AIJUiByx9ua1ld+xSuP/ZsREJU3e0LEzW/aYoSLzPVLsY5dDHp5OU3S2ShkEC4oXhGrhXpVQNwskLAWuozYodxXulvRgsSe2bPSOb8Z0n1RokGdIeUSRDQI/vAl+E=",
	 
	 "sign_alipay"  => "slmLhF2r3iMfxSACaZjoj9ZetPEz3IsN6DrdPEnMYlmKdVfvXLF+Ec8AbKNDnsEzjdl3r/EcxuboNZn7XpdT++bPC7cYenE04waA/Csafsu5QRfNJEP1iA8ZTJzP7Avb",
	 
	 "user_register" =>"C6ZK8rLtzq5XyqKAB7DFMBWbiE6/Uw2Ayui+GrZYmv2IAOrZO86HUkaNw0rpxBSmntI0sUBZIN/vZqMyLw4lrA==",
	 
	 "yuanpian_gettoken" =>"fH3aAhE5E0I=",
	 
	 "user_login"  => "C6ZK8rLtzq5XyqKAB7DFMMrovhq2WJr9iADq2TvOh1JGjcNK6cQUpkNETrK+H1w8Afuckau8rODx28km8ax4+w==",
	 
	 "uu_login" =>"C6ZK8rLtzq5XyqKAB7DFMMrovhq2WJr9iADq2TvOh1JGjcNK6cQUpnDtJnBS5GPDrj2P2DTwOpoWd9mSP9Smgw=="
	 
    ];

	//  rnd 安卓手机的imei  
	
    foreach ($mk as $key => $value) {
        # code...
            echo "$key=>".urldecode($es->decrypt($value));
            echo "<br/><br/>";
    }

