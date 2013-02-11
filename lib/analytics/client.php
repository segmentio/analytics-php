<?php

require(dirname(__FILE__) . '/consumers/consumer.php');
require(dirname(__FILE__) . '/consumers/queue_consumer.php');

require(dirname(__FILE__) . '/consumers/file.php');
require(dirname(__FILE__) . '/consumers/fork_curl.php');
require(dirname(__FILE__) . '/consumers/socket.php');


class Analytics_Client {

  private $consumer;

  /**
   * Create a new analytics object with your app's secret
   * key
   *
   * @param string $secret
   * @param array  $options array of consumer options [optional]
   * @param string Consumer constructor to use, socket by default.
   */
  public function __construct($secret, $options = array()) {

    $consumers = array(
      "socket"     => "Analytics_SocketConsumer",
      "file"       => "Analytics_FileConsumer",
      "fork_curl"  => "Analytics_ForkCurlConsumer"
    );

    # Use our socket consumer by default
    $consumer_type = isset($options["consumer"]) ? $options["consumer"] :
                                                   "socket";
    $Consumer = $consumers[$consumer_type];

    $this->consumer = new $Consumer($secret, $options);
  }

  public function __destruct() {
    $this->consumer->__destruct();
  }

  /**
   * Tracks a user action
   * @param  [string] $user_id    user id string
   * @param  [string] $event      name of the event
   * @param  [array]  $properties properties associated with the event [optional]
   * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
   * @return [boolean] whether the track call succeeded
   */
  public function track($user_id, $event, $properties = array(),
                        $timestamp = null, $context = array()) {

    $context = array_merge($context, $this->getContext());

    $timestamp = $this->formatTime($timestamp);

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
  public function identify($user_id, $traits = array(), $timestamp = null,
                            $context = array()) {

    $context = array_merge($context, $this->getContext());

    $timestamp = $this->formatTime($timestamp);

    return $this->consumer->identify($user_id, $traits, $context,
                                      $timestamp);
  }

  /**
   * Formats a timestamp by making sure it is set, and then converting it to
   * iso8601 format.
   * @param  time $timestamp - time in seconds (time())
   */
  private function formatTime($timestamp) {

    if ($timestamp == null) $timestamp = time();

    # Format for iso8601
    return date("c", $timestamp);
  }


  /**
   * Add the segment.io context to the request
   * @return array additional context
   */
  private function getContext () {
    return array( "library" => "analytics-php" );
  }
}
?>