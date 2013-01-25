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
    public function __construct($secret, $options = array()) {
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
    public function track($user_id, $event, $properties = array(),
                          $timestamp = null) {

        $context = $this->get_context();

        $this->consumer.track($user_id, $event, $properties, $context,
                              $timestamp);
    }

    public static function identify($user_id, $traits = array(),
                                    $timestamp = null) {

        $context = $this->get_context();

        $this->consumer.identify($user_id, $event, $properties, $context,
                                 $timestamp);
    }


    private function get_context () {
        return array( 'library' => 'analytics-python' );
    }
}

$client = new Analytics_Client('aaa');

?>