<?php

namespace Segment;

use Segment\Consumer\ConsumerInterface;

class Analytics {

  private $client;
  
  CONST VERSION = "1.4.2";

  /**
   * Sets a consumer for the Analytics
   *
   * @param ConsumerInterface $consumer
   * @throws \Exception
   */
  public function __construct(ConsumerInterface $consumer) {
    if (!function_exists('json_encode')) {
        throw new \Exception('Segment needs the JSON PHP extension.');
    }
    $this->client = $consumer;
  }

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public function track(array $message) {
    $this->checkClient();
    $event = !empty($message["event"]);
    $this->assert($event, "Segment::track() expects an event");
    $this->validate($message, "track");

    $message = $this->message($message, "properties");
    $message["type"] = "track";
    
    return $this->client->track($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public function identify(array $message) {
    $this->checkClient();
    $message["type"] = "identify";
    $this->validate($message, "identify");

    $message = $this->message($message, "traits");
    $message["type"] = "identify";

    return $this->client->identify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public function group(array $message) {
    $this->checkClient();
    $groupId = !empty($message["groupId"]);
    $userId = !empty($message["userId"]);
    $this->assert($groupId && $userId, "Segment::group() expects userId and groupId");

    $message = $this->message($message, "traits");
    $message["type"] = "group";

    return $this->client->group($message);
  }

  /**
   * Tracks a page view
   *
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public function page(array $message) {
    $this->checkClient();
    $this->validate($message, "page");

    $message = $this->message($message, "properties");
    $message["type"] = "page";

    return $this->client->page($message);
  }

  /**
   * Tracks a screen view
   *
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public function screen(array $message) {
    $this->checkClient();
    $this->validate($message, "screen");

    $message = $this->message($message, "properties");
    $message["type"] = "screen";

    return $this->client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $from      user id to alias from
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    $this->checkClient();
    $userId = !empty($message["userId"]);
    $previousId = !empty($message["previousId"]);
    $this->assert($userId && $previousId, "Segment::alias() requires both userId and previousId");

    $message = $this->message($message);
    $message["type"] = "alias";

    return $this->client->alias($message);
  }

  /**
   * Flush the client
   */
  public function flush(){
    $this->checkClient();
    return $this->client->flush();
  }

  /**
   * Validate common properties.
   *
   * @param array $msg
   * @param string $type
   */
  public function validate($msg, $type){
    $userId = !empty($msg["userId"]);
    $anonId = !empty($msg["anonymousId"]);
    $this->assert($userId || $anonId, "Segment::$type() requires userId or anonymousId");
  }

  /**
   * Check the client.
   *
   * @throws \Exception
   */
  private function checkClient(){
    if (null != $this->client) return;
    throw new \Exception("Segment::init() must be called before any other tracking method.");
  }

  /**
   * Assert `value` or throw.
   *
   * @param array $value
   * @param string $msg
   * @throws \Exception
   */
  private function assert($value, $msg){
    if (!$value) throw new \Exception($msg);
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
