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

    return $this->client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    $this->checkClient();
    $userId = !empty($message["userId"]);
    $previousId = !empty($message["previousId"]);
    $this->assert($userId && $previousId, "Segment::alias() requires both userId and previousId");

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
    throw new \Exception("Client must be set before any other tracking method.");
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
}
