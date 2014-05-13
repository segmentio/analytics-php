<?php

require(__DIR__ . '/Consumer.php');
require(__DIR__ . '/QueueConsumer.php');
require(__DIR__ . '/Consumer/File.php');
require(__DIR__ . '/Consumer/ForkCurl.php');
require(__DIR__ . '/Consumer/Socket.php');

class Analytics_Client {

  /**
   * VERSION
   */

  const VERSION = "1.0.0";

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
      "socket"     => "Analytics_Consumer_Socket",
      "file"       => "Analytics_Consumer_File",
      "fork_curl"  => "Analytics_Consumer_ForkCurl"
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
   * 
   * @param  array $message
   * @return [boolean] whether the track call succeeded
   */
  public function track(array $message) {
    $message = $this->message($message);
    $message["type"] = "track";
    return $this->consumer->track($message);
  }

  /**
   * Tags traits about the user.
   * 
   * @param  [array] $message
   * @return [boolean] whether the track call succeeded
   */
  public function identify(array $message) {
    $message = $this->message($message);
    $message["type"] = "identify";
    return $this->consumer->identify($message);
  }

  /**
   * Aliases from one user id to another
   * 
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    $message = $this->message($message);
    $message["type"] = "alias";
    return $this->consumer->alias($message);
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
   * Add common fields to the gvien `message`
   * 
   * @param array $msg
   * @return array
   */

  private function message($msg){
    if (!isset($msg["context"])) $msg["context"] = array();
    if (!isset($msg["timestamp"])) $msg["timestamp"] = null;
    $msg["context"] = array_merge($msg["context"], $this->getContext());
    $msg["timestamp"] = $this->formatTime($msg["timestamp"]);
    return $msg;
  }

  /**
   * Add the segment.io context to the request
   * @return array additional context
   */
  private function getContext () {
    return array(
      "library" => array(
        "name" => "analytics-php",
        "version" => self::VERSION
      )
    );
  }
}
