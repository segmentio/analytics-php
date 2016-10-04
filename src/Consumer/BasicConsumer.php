<?php

namespace Segment\Consumer;

use Segment\Analytics;

abstract class BasicConsumer implements ConsumerInterface {

  protected $type = "Consumer";

  protected $options;
  protected $secret;

  /**
   * Tracks a user action
   *
   * @param  array  $message
   * @return boolean whether the track call succeeded
   */
  abstract protected function doTrack(array $message);

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  abstract protected function doIdentify(array $message);

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  abstract protected function doGroup(array $message);

  /**
   * Tracks a page view.
   *
   * @param  array  $message
   * @return boolean whether the page call succeeded
   */
  abstract protected function doPage(array $message);

  /**
   * Tracks a screen view.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  abstract protected function doScreen(array $message);

  /**
   * Aliases from one user id to another
   *
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  abstract protected function doAlias(array $message);

  /**
   * Store our secret and options as part of this consumer
   * @param string $secret
   * @param array  $options
   */
  public function __construct($secret, $options = array()) {
    $this->secret = $secret;
    $this->options = $options;
  }

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return [boolean] whether the track call succeeded
   */
  public function track(array $message) {
    $message = $this->message($message, "properties");
    $message["type"] = "track";
    return $this->doTrack($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  [array] $message
   * @return [boolean] whether the track call succeeded
   */
  public function identify(array $message) {
    $message = $this->message($message, "traits");
    $message["type"] = "identify";
    return $this->doIdentify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  [array] $message
   * @return [boolean] whether the group call succeeded
   */
  public function group(array $message) {
    $message = $this->message($message, "traits");
    $message["type"] = "group";
    return $this->doGroup($message);
  }

  /**
   * Tracks a page view.
   *
   * @param  [array] $message
   * @return [boolean] whether the page call succeeded
   */
  public function page(array $message) {
    $message = $this->message($message, "properties");
    $message["type"] = "page";
    return $this->doPage($message);
  }

  /**
   * Tracks a screen view.
   *
   * @param  [array] $message
   * @return [boolean] whether the screen call succeeded
   */
  public function screen(array $message) {
    $message = $this->message($message, "properties");
    $message["type"] = "screen";
    return $this->doScreen($message);
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
    return $this->doAlias($message);
  }

  /**
   * Check whether debug mode is enabled
   * @return boolean
   */
  protected function debug() {
    return isset($this->options["debug"]) ? $this->options["debug"] : false;
  }

  /**
   * Check whether we should connect to the API using SSL. This is enabled by
   * default with connections which make batching requests. For connections
   * which can save on round-trip times, you may disable it.
   * @return boolean
   */
  protected function ssl() {
    return isset($this->options["ssl"]) ? $this->options["ssl"] : true;
  }


  /**
   * On an error, try and call the error handler, if debugging output to
   * error_log as well.
   * @param  string $code
   * @param  string $msg
   */
  protected function handleError($code, $msg) {

    if (isset($this->options['error_handler'])) {
      $handler = $this->options['error_handler'];
      $handler($code, $msg);
    }

    if ($this->debug()) {
      error_log("[Analytics][" . $this->type . "] " . $msg);
    }
  }

  /**
   * Formats a timestamp by making sure it is set
   * and converting it to iso8601.
   *
   * The timestamp can be time in seconds `time()` or `microseconds(true)`.
   * any other input is considered an error and the method will return a new date.
   *
   * Note: php's date() "u" format (for microseconds) has a bug in it
   * it always shows `.000` for microseconds since `date()` only accepts
   * ints, so we have to construct the date ourselves if microtime is passed.
   *
   * @param string $timestamp - time in seconds (time())
   */
  private function formatTime($ts) {
    // time()
    if ($ts == null || !$ts) $ts = time();
    if (filter_var($ts, FILTER_VALIDATE_INT) !== false) return date("c", (int) $ts);

    // anything else try to strtotime the date.
    if (filter_var($ts, FILTER_VALIDATE_FLOAT) === false) {
      if (is_string($ts)) {
        return date("c", strtotime($ts));
      } else {
        return date("c");
      }
    }

    // fix for floatval casting in send.php
    $parts = explode(".", (string)$ts);
    if (!isset($parts[1])) return date("c", (int)$parts[0]);

    // microtime(true)
    $sec = (int)$parts[0];
    $usec = (int)$parts[1];
    $fmt = sprintf("Y-m-d\TH:i:s%sP", $usec);
    return date($fmt, (int)$sec);
  }

  /**
   * Add common fields to the gvien `message`
   *
   * @param array $msg
   * @param string $def
   * @return array
   */
  private function message($msg, $def = ""){
    if ($def && !isset($msg[$def])) $msg[$def] = array();
    if ($def && empty($msg[$def])) $msg[$def] = (object)$msg[$def];
    if (!isset($msg["context"])) $msg["context"] = array();
    if (!isset($msg["timestamp"])) $msg["timestamp"] = null;
    $msg["context"] = array_merge($msg["context"], $this->getContext());
    $msg["timestamp"] = $this->formatTime($msg["timestamp"]);
    $msg["messageId"] = $this->messageId();
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
            "version" => Analytics::VERSION
        )
    );
  }
}
