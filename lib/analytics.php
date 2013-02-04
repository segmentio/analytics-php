<?php

if (!function_exists('json_encode')) {
    throw new Exception('Analytics needs the JSON PHP extension.');
}

require(dirname(__FILE__) . '/analytics/client.php');


class Analytics {

  private static $client;

  /**
   * Initializes the default client to use. Uses the socket consumer by default.
   * @param  string $secret   your project's secret key
   * @param  array  $options  passed straight to the client
   */
  public static function init($secret, $options = array()) {

    self::$client = new Analytics_Client($secret, $options);
  }

  /**
   * Tracks a user action
   * @param  [string] $user_id    user id string
   * @param  [string] $event      name of the event
   * @param  [array]  $properties properties associated with the event [optional]
   * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
   * @return [boolean] whether the track call succeeded
   */
  public static function track($user_id, $event, $properties = null,
                                $timestamp = null, $context = array()) {
    self::check_client();
    return self::$client->track($user_id, $event, $properties, $timestamp,
                                $context);
  }

  /**
   * Tags traits about the user.
   * @param  string  $user_id
   * @param  array   $traits
   * @param  number  $timestamp  unix seconds since epoch (time()) [optional]
   * @param  array
   * @return boolean whether the track call succeeded
   */
  public static function identify($user_id, $traits = array(),
                                    $timestamp = null, $context = array()) {
    self::check_client();
    return self::$client->identify($user_id, $traits, $timestamp, $context);
  }

  /**
   * Ensures that the client is indeed set. Throws an exception when not set.
   */
  private static function check_client() {

    if (self::$client == null) {
      throw new Exception("Analytics::init must be called " .
                          "before track or identify");
    }
  }
};
?>
