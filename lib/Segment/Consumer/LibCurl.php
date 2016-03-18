<?php

class Segment_Consumer_LibCurl extends Segment_QueueConsumer {

  protected $type = "LibCurl";

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

    $body = $this->payload($messages);
    $payload = json_encode($body);
    $secret = $this->secret;

    $protocol = $this->ssl() ? "https://" : "http://";
    $host = "api.segment.io";
    $path = "/v1/import";
    $url = $protocol . $host . $path;

    // open connection
    $ch = curl_init();

    // set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // set variables for headers
    $header = array();
    $header[] = 'Content-Type: application/json';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // execute post
    curl_exec($ch);

    if ($this->debug()) {

      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($httpCode != 200) {
        $this->handleError($ch, $httpCode);
      }

      return $httpCode;
    }

    //close connection
    curl_close($ch);
  }
}
