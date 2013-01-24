<?php

require(dirname(__FILE__) . '/consumers/request.php');

class Analytics_Client {

    private $consumer;

    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param [String] $secret
     */
    public function __construct($secret, $options=array()) {
        $consumer = new Analytics_RequestConsumer($secret);
        $this->consumer = $consumer;
    }


    /**
     * Tracks a user action
     * @param  [type] $user_id    [description]
     * @param  [type] $event      [description]
     * @param  array  $properties [description]
     * @return [type]             [description]
     */
    public function track($user_id, $event, $properties=array()) {
        $Flusher::track($user_id, $event, $properties);
    }

    public static function identify($user_id, $traits=array()) {
        $Flusher = self::$Flusher;
        $Flusher::identify($user_id, $event, $properties);
    }
}

$client = new Analytics_Client('aaa');

?>