<?php

require_once __DIR__ . '/Segment/Client.php';

class Segment {
  private static $client;

  /**
   * Initializes the default client to use. Uses the libcurl consumer by default.
   * @param  string $secret   your project's secret key
   * @param  array  $options  passed straight to the client
   */
  public static function init($secret, $options = array()) {
    self::assert($secret, "Segment::init() requires secret");
    self::$client = new Segment_Client($secret, $options);
  }

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public static function track(array $message) {
    self::checkClient();
    $event = !empty($message["event"]);
    self::assert($event, "Segment::track() expects an event");
    self::validate($message, "track");

    return self::$client->track($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public static function identify(array $message) {
    self::checkClient();
    $message["type"] = "identify";
    self::validate($message, "identify");

    return self::$client->identify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public static function group(array $message) {
    self::checkClient();
    $groupId = !empty($message['groupId']);
    self::assert($groupId, "Segment::group() expects a groupId");    
    self::validate($message, "group");

    return self::$client->group($message);
  }

  /**
   * Tracks a page view
   *
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public static function page(array $message) {
    self::checkClient();
    self::validate($message, "page");

    return self::$client->page($message);
  }

  /**
   * Tracks a screen view
   *
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public static function screen(array $message) {
    self::checkClient();
    self::validate($message, "screen");

    return self::$client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $from      user id to alias from
   * @return boolean whether the alias call succeeded
   */
  public static function alias(array $message) {
    self::checkClient();
    $userId = (array_key_exists('userId', $message) && strlen((string) $message['userId']) > 0);
    $previousId = (array_key_exists('previousId', $message) && strlen((string) $message['previousId']) > 0);
    self::assert($userId && $previousId, "Segment::alias() requires both userId and previousId");

    return self::$client->alias($message);
  }

  /**
   * Validate common properties.
   *
   * @param array $message
   * @param string $type
   */
  public static function validate($message, $type){
    $userId = (array_key_exists('userId', $message) && strlen((string) $message['userId']) > 0);
    $anonId = !empty($message['anonymousId']);
    self::assert($userId || $anonId, "Segment::${type}() requires userId or anonymousId");
  }

  /**
   * Flush the client
   */

  public static function flush(){
    self::checkClient();

    return self::$client->flush();
  }

  /**
   * Check the client.
   *
   * @throws Exception
   */
  private static function checkClient(){
    if (null != self::$client) {
      return;
    }

    throw new Exception("Segment::init() must be called before any other tracking method.");
  }

  /**
   * Assert `value` or throw.
   *
   * @param array $value
   * @param string $msg
   * @throws Exception
   */
  private static function assert($value, $msg) {
    if (!$value) {
      throw new Exception($msg);
    }
  }
}

if (!function_exists('json_encode')) {
  throw new Exception('Segment needs the JSON PHP extension.');
}
