<?php

class Segment_Consumer_LibCurl extends Segment_QueueConsumer {
  protected $type = "LibCurl";

  /**
   * Creates a new queued libcurl consumer
   * @param string $secret
   * @param array  $options
   *     boolean  "debug" - whether to use debug output, wait for response.
   *     number   "max_queue_size" - the max size of messages to enqueue
   *     number   "batch_size" - how many messages to send in a single request
   */
  public function __construct($secret, $options = array()) {
    parent::__construct($secret, $options);
  }

  //define getter method for consumer type
  public function getConsumer() {
    return $this->type;
  }

  /**
   * Make a sync request to our API. If debug is
   * enabled, we wait for the response
   * and retry once to diminish impact on performance.
   * @param  array   $messages array of all the messages to send
   * @return boolean whether the request succeeded
   */
  public function flushBatch($messages) {
    $body = $this->payload($messages);
    $payload = json_encode($body);
    $secret = $this->secret;

    // Verify message size is below than 32KB
    if (strlen($payload) >= 32 * 1024) {
      if ($this->debug()) {
        $msg = "Message size is larger than 32KB";
        error_log("[Analytics][" . $this->type . "] " . $msg);
      }

      return false;
    }

    $protocol = $this->ssl() ? "https://" : "http://";
    if ($this->host) {
      $host = $this->host;
    } else {
      $host = "api.segment.io";
    }
    $path = "/v1/import";
    $url = $protocol . $host . $path;

    $backoff = 100;     // Set initial waiting time to 100ms

    while ($backoff < $this->maximum_backoff_duration) {
      $start_time = microtime(true);

      // open connection
      $ch = curl_init();

      // set the url, number of POST vars, POST data
      curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

      // set variables for headers
      $header = array();
      $header[] = 'Content-Type: application/json';

      // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
      $library = $messages[0]['context']['library'];
      $libName = $library['name'];
      $libVersion = $library['version'];
      $header[] = "User-Agent: ${libName}/${libVersion}";

      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // retry failed requests just once to diminish impact on performance
      $httpResponse = $this->executePost($ch);

      //close connection
      curl_close($ch);

      $elapsed_time = microtime(true) - $start_time;

      if (200 != $httpResponse) {
        // log error
        $this->handleError($ch, $httpResponse);

        if (($httpResponse >= 500 && $httpResponse <= 600) || 429 == $httpResponse) {
          // If status code is greater than 500 and less than 600, it indicates server error
          // Error code 429 indicates rate limited.
          // Retry uploading in these cases.
          usleep($backoff * 1000);
          $backoff *= 2;
        } elseif ($httpResponse >= 400) {
          break;
        }
      } else {
        break;  // no error
      }
    }

    return $httpResponse;
  }

  public function executePost($ch) {
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $httpCode;
  }
}
