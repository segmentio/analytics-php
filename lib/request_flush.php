<?php

class RequestFlusher {

    public static $secret;

    public static function RequestFlusher($secret) {
        self::$secret = $secret;
    }

    public static function track ($user_id, $event) {
        self::request('track',
                      array('secret' => self::$secret,



    }

    public static function identify ($user_id, $traits=array()) {
    }

    private static function request($type, $body) {

    }
}
?>

