<?php

namespace SegmentIO;

class Analytics_Consumer_ForkCurl extends Analytics_QueueConsumer {

  protected $type = "ForkCurl";


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
  }

  /**
   * Make an async request to our API. Fork a curl process, immediately send
   * to the API. If debug is enabled, we wait for the response.
   * @param  array   $messages array of all the messages to send
   * @return boolean whether the request succeeded
   */
  public function flushBatch($messages) {

    $body = array(
      "batch"  => $messages,
      "secret" => $this->secret
    );

    $payload = json_encode($body);

    # Escape for shell usage.
    $payload = escapeshellarg($payload);

    $protocol = $this->ssl() ? "https://" : "http://";
    $host = "api.segment.io";
    $path = "/v1/import";
    $url = $protocol . $host . $path;

    $cmd = "curl -X POST -H 'Content-Type: application/json'";
    $cmd.= " -d " . $payload . " '" . $url . "'";

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
