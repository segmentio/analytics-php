<?php

namespace Segment;

use Segment\Consumer\ConsumerInterface;
use Segment\Consumer\LibCurlConsumer;

class Analytics {

  const VERSION = "1.4.2";

  const DEFAULT_CONSUMER = 'Segment\Consumer\LibCurlConsumer';

  private $client;

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
   * Return an instance of this class with a dynamically generated instance of the consumer based on passed arguments
   *
   * @param $secret
   * @param array $options
   * @return Analytics
   * @throws \Exception
   */
  public static function factory($secret, $options = array()) {
    $class = isset($options['consumer']) ? $options['consumer'] : self::DEFAULT_CONSUMER;
    unset($options['consumer']);
    if (!class_exists($class)) {
      throw new \Exception('Given class ' . $class . ' does not exist');
    }
      return new Analytics(new $class($secret, $options));
  }

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public function track(array $message) {
    $event = !empty($message["event"]);
    $this->assert($event, "Segment::track() expects an event");
    $this->validate($message, "track");

    return $this->client->track($message);
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
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public function identify(array $message) {
    $message["type"] = "identify";
    $this->validate($message, "identify");

    return $this->client->identify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public function group(array $message) {
    $groupId = !empty($message["groupId"]);
    $userId = !empty($message["userId"]);
    $this->assert($groupId && $userId, "Segment::group() expects userId and groupId");

    return $this->client->group($message);
  }

  /**
   * Tracks a page view
   *
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public function page(array $message) {
    $this->validate($message, "page");

    return $this->client->page($message);
  }

  /**
   * Tracks a screen view
   *
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public function screen(array $message) {
    $this->validate($message, "screen");

    return $this->client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    $userId = !empty($message["userId"]);
    $previousId = !empty($message["previousId"]);
    $this->assert($userId && $previousId, "Segment::alias() requires both userId and previousId");

    return $this->client->alias($message);
  }

  /**
   * Flush the client
   */
  public function flush(){
    return $this->client->flush();
  }
}
