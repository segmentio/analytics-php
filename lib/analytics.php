<?php

require 'request_flush.php';

class Analytics {

    public $secret;
    public static $FlusherType = 'RequestFlusher';
    private static

    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param [String] $secret
     */
    public static function init($secret, $options=array()) {
        self::$secret = $secret;
    }


    /**
     * Tracks a user action
     * @param  [type] $user_id    [description]
     * @param  [type] $event      [description]
     * @param  array  $properties [description]
     * @return [type]             [description]
     */
    public static function track($user_id, $event, $properties=array()) {
        $Flusher = self::$Flusher;
        $Flusher::track($user_id, $event, $properties);
    }

    public static function identify($user_id, $traits=array()) {
        $Flusher = self::$Flusher;
        $Flusher::identify($user_id, $event, $properties);
    }
}

Analytics::init('aaaaa');
?>