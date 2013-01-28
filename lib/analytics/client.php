<?php

require(dirname(__FILE__) . '/consumers/socket.php');

class Analytics_Client {

    private $consumer;

    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param [String] $secret
     */
    public function __construct($secret, $options = array()) {
        $this->consumer = new Analytics_RequestConsumer($secret);
    }


    /**
     * Tracks a user action
     * @param  [type] $user_id    [description]
     * @param  [type] $event      [description]
     * @param  array  $properties [description]
     * @return [type]             [description]
     */
    public function track($user_id, $event, $properties = null,
                          $timestamp = null) {

        $context = $this->get_context();

        if ($timestamp == null) {
            $timestamp = time();
        }

        $this->consumer->track($user_id, $event, $properties, $context,
                              $timestamp);
    }

    public static function identify($user_id, $traits = array(),
                                    $timestamp = null) {

        $context = $this->get_context();

        if ($timestamp == null) {
            $timestamp = time();
        }


        $this->consumer.identify($user_id, $event, $properties, $context,
                                 $timestamp);
    }


    private function get_context () {
        return array( 'library' => 'analytics-php' );
    }
}

$client = new Analytics_Client('testsecret');
$client->track('Some User', 'New Test PHP Event');
sleep(1);
?>