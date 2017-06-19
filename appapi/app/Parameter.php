<?php
namespace App;

use App\Exceptions\Exception;

class Parameter
{
    protected $_data;

    public function __construct($data) {
        $this->_data = $data;
    }

    public function set($key, $value) {
        $this->_data[$key] = $value;
    }

    public function get($key, $default = null, $type_fun_regex = null) {
        $data = @$this->_data[$key];

        if($data === null || $data === '') {
            return $default;
        }

        if(is_string($type_fun_regex) && method_exists($this, $type_fun_regex)) {
            return $this->$type_fun_regex($data);
        }

        if(is_callable($type_fun_regex)) {
            return $type_fun_regex($data);
        }

        if(is_string($type_fun_regex)) {
            if(preg_match($type_fun_regex, $data)) {
                return $data;
            } else {
                throw new Exception (trans('messages.param_format_error', ['key' => $key]), 0);
            }
        }

        return $data;
    }

    public function tough($key, $type_fun_regex = null) {
        $data = @$this->_data[$key];
        if($data === null || $data === '') {
            throw new Exception (trans('messages.param_missing', ['key' => $key]), 0);
        }

        if(is_string($type_fun_regex) && method_exists($this, $type_fun_regex)) {
            return $this->$type_fun_regex($data);
        }

        if(is_callable($type_fun_regex)) {
            return $type_fun_regex($data);
        }

        if(is_string($type_fun_regex)) {
            if(preg_match($type_fun_regex, $data)) {
                return $data;
            } else {
                throw new Exception (trans('messages.param_format_error', ['key' => $key]), 0);
            }
        }

        return $data;
    }

    // TODO 如何获取到 $this->parameter->get('_version') ???
    //public function vget($key, $version, $default = null, $type_fun_regex = null) {
    //    list($s, $v) = explode(' ', $version);
    //    if(version_compare())
    //}

    protected function mobile($mobile) {
        $mobile = trim($mobile, '　 ');

        if(!preg_match('/^1\d{10}$/', $mobile)) {
            throw new Exception (trans('messages.mobile_format_error', ['mobile' => $mobile]), 0);
        }

        return $mobile;
    }

    protected function username($username) {
        $username = trim($username, '　 ');

        if(preg_match('/^\d+$/', $username)) {
            throw new Exception (trans('messages.useranme_format_error_d'), 0);
        }

        if(strlen($username) < 6 || strlen($username) > 15) {
            throw new Exception (trans('messages.useranme_format_error_l'), 0);
        }

        if(preg_match('/[^a-zA-Z0-9]+/', $username)) {
            throw new Exception (trans('messages.useranme_format_error_dw'), 0);
        }

        return $username;
    }

    protected function smscode($smscode) {
        $smscode = trim($smscode, '　 ');

        if(strlen($smscode) != 6) {
            throw new Exception (trans('messages.invalid_smscode'), 0);
        }

        return $smscode;
    }

    protected function url($url) {
        $url = trim($url, '　 ');

        if(!preg_match('/^https*:\/\/.*$/', $url)) {
            throw new Exception (trans('messages.url_format_error', ['url' => $url]), 0);
        }

        return $url;
    }

    protected function password($password) {
        $password = trim($password, '　 ');
        if($password == "") throw new Exception (trans('messages.password_empty'), 0);

        return $password;
    }

    protected function nickname($nickname) {
        $nickname = trim($nickname, '　 ');

        $len1 = mb_strlen($nickname, 'UTF-8');
        $len2 = strlen($nickname);

        $len = $len1 + ($len2 - $len1) / 2;

        if($len > 14) {
            throw new Exception (trans('messages.nickname_format_error_l'), 0);
        }

        return $nickname;
    }

    protected function sub_nickname($nickname) {
        $nickname = trim($nickname, '　 ');

        $len1 = mb_strlen($nickname, 'UTF-8');
        $len2 = strlen($nickname);

        $len = $len1 + ($len2 - $len1) / 2;

        if($len > 10) {
            throw new Exception (trans('messages.subnickname_format_error_l'), 0);
        }

        return $nickname;
    }
}
