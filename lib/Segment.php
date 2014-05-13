<?php

if (!function_exists('json_encode')) {
    throw new Exception('Segment needs the JSON PHP extension.');
}

require(dirname(__FILE__) . '/Segment/Client.php');


class Segment {

  private static $client;

  /**
   * Initializes the default client to use. Uses the socket consumer by default.
   * @param  string $secret   your project's secret key
   * @param  array  $options  passed straight to the client
   */
  public static function init($secret, $options = array()) {

  	if (!$secret){
  		throw new Exception("Segment::init Secret parameter is required");
  	}

    self::$client = new Segment_Client($secret, $options);
  }

  /**
   * Tracks a user action
   * 
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public static function track(array $message) {
    self::check_client();
    return self::$client->track($message);
  }

  /**
   * Tags traits about the user.
   * 
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public static function identify(array $message) {
    self::check_client();
    return self::$client->identify($message);
  }

  /**
   * Tags traits about the group.
   * 
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public static function group(array $message) {
    self::check_client();
    return self::$client->group($message);
  }

  /**
   * Tracks a page view
   * 
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public static function page(array $message) {
    self::check_client();
    return self::$client->page($message);
  }

  /**
   * Tracks a screen view
   * 
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public static function screen(array $message) {
    self::check_client();
    return self::$client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   * 
   * @param  array $from      user id to alias from
   * @return boolean whether the alias call succeeded
   */
  public static function alias(array $message) {
    self::check_client();
    return self::$client->alias($message);
  }

  /**
   * Ensures that the client is indeed set. Throws an exception when not set.
   */
  private static function check_client() {

    if (self::$client == null) {
      throw new Exception("Segment::init must be called " .
                          "before track or identify");
    }
  }
}
