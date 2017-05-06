<?php
namespace App;

use App\Redis;

class Session {


    protected $data = [];
    protected $_data = [];
    protected $exists = false;
    protected $rediskey;

    public function __construct($token) {
        $this->rediskey = static::rediskey($token);
        $this->data['token'] = $token;
        $this->_data['token'] = $token;
    }

    public static function find($token) {
        $data = Redis::HGETALL(static::rediskey($token));
        if(!$data) return null;

        $instance = new static($token);
        $instance->data = $data;
        $instance->exists = true;

        return $instance;
    }

    public function __set($property, $value) {
        $this->data[$property] = $value;
        $this->_data[$property] = $value;
    }

    public function __get($property) {
        return @$this->data[$property];
    }

    public function save() {
        $_params = [];
        foreach($this->_data as $k => $v) {
            $_params[] = $k;
            $_params[] = $v;
        }

        if(!$this->exists) {
            $_params[] = 'created_at';
            $_params[] = date('Y-m-d H:i:s');
        }

        Redis::HMSET($this->rediskey, ...$_params);
        if($this->exists == false) {
            Redis::SADD('us_' . $this->data['ucid'], $this->rediskey);
            Redis::EXPIRE($this->rediskey, 2592000);
        }
    }

    protected static function rediskey($token) {
        return 's_' . $token;
    }
}