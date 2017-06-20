<?php
namespace App;

class MessageService
{
    const SendMessageUri = 'msa/v4.2/account_message';

    static $config = null;

    protected static function getConfig() {
        if(!static::$config) {
            static::$config = configex('common.MessageService');
        }

        return static::$config;
    }

    protected static function HTTPRequest($uri, $data) {
        $config = static::getConfig();

        $url = $config['baseurl'] . $uri;

        return http_curl($url, $data, true, [], 'json');
    }

    public static function SendMessage($ucid, $title, $content, $description = "") {
        $data = [
            'ucid' => $ucid,
            'title' => $title,
            'content' => $content,
            'description' => $description,
        ];

        return static::HTTPRequest(static::SendMessageUri, $data);
    }

}
