<?php

require(dirname(__FILE__) . '/consumers/file.php');
require(dirname(__FILE__) . '/consumers/socket.php');


class Analytics_Client {

    private $consumer;

    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param [string] $secret
     * @param [array]  $options array of consumer options [optional]
     * @param [string] Consumer constructor to use, socket by default.
     */
    public function __construct($secret, $Consumer = "Analytics_SocketConsumer",
                                $options = array()) {
        $this->consumer = new $Consumer($secret, $options);
    }


    /**
     * Tracks a user action
     * @param  [string] $user_id    user id string
     * @param  [string] $event      name of the event
     * @param  [array]  $properties properties associated with the event [optional]
     * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
     * @return [boolean] whether the track call succeeded
     */
    public function track($user_id, $event, $properties = null,
                          $timestamp = null) {

        $context = $this->get_context();

        $timestamp = $this->format_time($timestamp);

        return $this->consumer->track($user_id, $event, $properties, $context,
                                      $timestamp);
    }

    /**
     * Tags traits about the user.
     * @param  [string] $user_id
     * @param  [array]  $traits
     * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
     * @return [boolean] whether the track call succeeded
     */
    public function identify($user_id, $traits = array(), $timestamp = null) {

        $context = $this->get_context();

        $timestamp = $this->format_time($timestamp);

        return $this->consumer->identify($user_id, $traits, $context,
                                         $timestamp);
    }

    /**
     * Formats a timestamp by making sure it is set, and then converting it to
     * iso8601 format.
     * @param  [time] $timestamp
     */
    private function format_time($timestamp) {

        if ($timestamp == null) $timestamp = time();

        return date("c", $timestamp);
    }


    /**
     * Add the segment.io context to the request
     * @return [type] [description]
     */
    private function get_context () {
        return array( "library" => "analytics-php" );
    }
}
?>