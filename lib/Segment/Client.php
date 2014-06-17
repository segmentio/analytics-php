<?php

require(__DIR__ . '/Consumer.php');
require(__DIR__ . '/QueueConsumer.php');
require(__DIR__ . '/Consumer/File.php');
require(__DIR__ . '/Consumer/ForkCurl.php');
require(__DIR__ . '/Consumer/Socket.php');

class Segment_Client {

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
      "socket"     => "Segment_Consumer_Socket",
      "file"       => "Segment_Consumer_File",
      "fork_curl"  => "Segment_Consumer_ForkCurl"
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
   * Tags traits about the group.
   *
   * @param  [array] $message
   * @return [boolean] whether the group call succeeded
   */
  public function group(array $message) {
    $message = $this->message($message);
    $message["type"] = "group";
    return $this->consumer->group($message);
  }

  /**
   * Tracks a page view.
   *
   * @param  [array] $message
   * @return [boolean] whether the page call succeeded
   */
  public function page(array $message) {
    $message = $this->message($message);
    $message["type"] = "page";
    return $this->consumer->page($message);
  }

  /**
   * Tracks a screen view.
   *
   * @param  [array] $message
   * @return [boolean] whether the screen call succeeded
   */
  public function screen(array $message) {
    $message = $this->message($message);
    $message["type"] = "screen";
    return $this->consumer->screen($message);
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
   * Flush any async consumers
   */
  public function flush() {
    if (!method_exists($this->consumer, 'flush')) return;
    $this->consumer->flush();
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
    $msg["messageId"] = self::messageId();
    return $msg;
  }

  /**
   * Generate a random messageId.
   *
   * https://gist.github.com/dahnielson/508447#file-uuid-php-L74
   *
   * @return string
   */

  private static function messageId(){
    return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x"
      , mt_rand(0, 0xffff)
      , mt_rand(0, 0xffff)
      , mt_rand(0, 0xffff)
      , mt_rand(0, 0x0fff) | 0x4000
      , mt_rand(0, 0x3fff) | 0x8000
      , mt_rand(0, 0xffff)
      , mt_rand(0, 0xffff)
      , mt_rand(0, 0xffff));
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
