<?php

class Segment_Consumer_LibCurl extends Segment_QueueConsumer {
  protected $type = "LibCurl";

  /**
   * Creates a new queued libcurl consumer
   * @param string $secret
   * @param array  $options
   *     boolean  "debug" - whether to use debug output, wait for response.
   *     number   "max_queue_size" - the max size of messages to enqueue
   *     number   "flush_at" - how many messages to send in a single request
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

    if ($this->compress_request) {
      $payload = gzencode($payload);
    }

    $protocol = $this->ssl() ? "https://" : "http://";
    if ($this->host) {
      $host = $this->host;
    } else {
      $host = "api.segment.io";
    }
    $path = "/v1/batch";
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

      if ($this->compress_request) {
        $header[] = 'Content-Encoding: gzip';
      }

      // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
      $library = $messages[0]['context']['library'];
      $libName = $library['name'];
      $libVersion = $library['version'];
      $header[] = "User-Agent: ${libName}/${libVersion}";

      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // retry failed requests just once to diminish impact on performance
      $responseContent = curl_exec($ch);

      $err = curl_error($ch);
      if ($err) {
        $this->handleError(curl_errno($ch), $err);
        return;
      }

      $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      //close connection
      curl_close($ch);

      $elapsed_time = microtime(true) - $start_time;

      if (200 != $responseCode) {
        // log error
        $this->handleError($responseCode, $responseContent);

        if (($responseCode >= 500 && $responseCode <= 600) || 429 == $responseCode) {
          // If status code is greater than 500 and less than 600, it indicates server error
          // Error code 429 indicates rate limited.
          // Retry uploading in these cases.
          usleep($backoff * 1000);
          $backoff *= 2;
        } elseif ($responseCode >= 400) {
          break;
        }
      } else {
        break;  // no error
      }
    }

    return $responseCode;
  }
}
