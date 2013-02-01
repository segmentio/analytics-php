<?php

class Analytics_ForkCurlConsumer extends Analytics_Consumer {

  protected $type = "Fork";
  private $queue;
  private $max_queue_size = 10000;
  private $batch_size = 100;

  /**
   * Creates a new queued fork consumer which queues fork and identify
   * calls before adding them to
   * @param string $secret
   * @param array  $options
   *     boolean  "debug" - whether to use debug output, wait for response.
   *     number   "max_queue_size" - the max size of messages to enqueue
   *     number   "batch_size" - how many messages to send in a single request
   */
  public function __construct($secret, $options = array()) {
    parent::__construct($secret, $options);

    $this->queue = array();

    if (isset($options["max_queue_size"]))
      $this->max_queue_size = $options["max_queue_size"];

    if (isset($options["batch_size"]))
      $this->batch_size = $options["batch_size"];
  }

  public function __destruct() {
    # Flush our queue on destruction
    $this->flush();
  }

  /**
   * Tracks a user action
   * @param  string  $user_id    user id string
   * @param  string  $event      name of the event
   * @param  array   $properties properties associated with the event
   * @param  array   $context
   * @param  string  $timestamp  iso8601 of the timestamp
   * @return boolean whether the track call succeeded
   */
  public function track($user_id, $event, $properties, $context, $timestamp) {

    $track = array(
      "secret"     => $this->secret,
      "userId"     => $user_id,
      "event"      => $event,
      "properties" => $properties,
      "timestamp"  => $timestamp,
      "context"    => $context,
      "action"     => "track"
    );

    return $this->enqueue($track);
  }

  /**
   * Tags traits about the user.
   * @param  string  $user_id
   * @param  array   $traits
   * @param  array   $context
   * @param  string  $timestamp   iso8601 of the timestamp
   * @return boolean whether the track call succeeded
   */
  public function identify($user_id, $traits, $context, $timestamp) {

    $identify = array(
      "secret"     => $this->secret,
      "userId"     => $user_id,
      "traits"     => $traits,
      "context"    => $context,
      "timestamp"  => $timestamp,
      "action"     => "identify"
    );

    return $this->enqueue($identify);
  }

  /**
   * Flushes our queue of messages by batching them to the server
   */
  public function flush() {

    $count = count($this->queue);
    $success = true;

    while($count > 0 && $success) {

      $batch = array_splice($this->queue, 0, min($this->batch_size, $count));
      $success = $this->request($batch);

      $count = count($this->queue);
    }

    return $success;
  }

  /**
   * Adds an item to our queue for processing later.
   * @param  array $item a track or identify
   */
  private function enqueue($item) {

    $count = count($this->queue);

    if ($count > $this->max_queue_size)
      return false;

    $count = array_push($this->queue, $item);

    if ($count > $this->batch_size)
      $this->flush();

    return true;
  }

  /**
   * Make an async request to our API. Fork a curl process, immediately send
   * to the API. If debug is enabled, we wait for the response.
   * @param  array   $messages array of all the messages to send
   * @return boolean whether the request succeeded
   */
  private function request($messages) {

    $body = array(
      "batch"  => $messages,
      "secret" => $this->secret
    );

    $payload = json_encode($body);

    # Replace our single quotes since we are using them in the terminal
    $payload = str_replace("'", "'\''", $payload);

    $protocol = $this->ssl() ? "https://" : "http://";
    $host = "api.segment.io";
    $path = "/v1/import";
    $url = $protocol . $host . $path;

    $cmd = "curl -X POST -H 'Content-Type: application/json'";
    $cmd.= " -d '" . $payload . "' " . "'" . $url . "'";

    if (!$this->debug()) {
      $cmd .= " > /dev/null 2>&1 &";
    }

    exec($cmd, $output, $exit);

    if ($exit != 0) {
      $this->handleError($exit, $output);
    }

    return $exit == 0;
  }
}
?>