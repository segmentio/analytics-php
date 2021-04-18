<?php

class Segment_Consumer_ForkCurl extends Segment_QueueConsumer {
  protected $type = "ForkCurl";

  /**
   * Creates a new queued fork consumer which queues fork and identify
   * calls before adding them to
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
   * Make an async request to our API. Fork a curl process, immediately send
   * to the API. If debug is enabled, we wait for the response.
   * @param  array   $messages array of all the messages to send
   * @return boolean whether the request succeeded
   */
  public function flushBatch($messages) {
    $body = $this->payload($messages);
    $payload = json_encode($body);

    // Escape for shell usage.
    $payload = escapeshellarg($payload);
    $secret = escapeshellarg($this->secret);

    $protocol = $this->ssl() ? "https://" : "http://";
    if ($this->host) {
      $host = $this->host;
    } else {
      $host = "api.segment.io";
    }
    $path = "/v1/batch";
    $url = $protocol . $host . $path;

    $cmd = "curl -u ${secret}: -X POST -H 'Content-Type: application/json'";
    
    $tmpfname = "";
    if ($this->compress_request) {
      // Compress request to file
      $tmpfname = tempnam("/tmp", "forkcurl_");
      $cmd2 = "echo " . $payload . " | gzip > " . $tmpfname;
      exec($cmd2, $output, $exit);

      if (0 != $exit) {
        $this->handleError($exit, $output);
        return false;
      }

      $cmd.= " -H 'Content-Encoding: gzip'";

      $cmd.= " --data-binary '@" . $tmpfname . "'";
    } else {
      $cmd.= " -d " . $payload;
    }
    
    $cmd.= " '" . $url . "'";

    // Verify message size is below than 32KB
    if (strlen($payload) >= 32 * 1024) {
      $msg = "Message size is larger than 32KB";
      error_log("[Analytics][" . $this->type . "] " . $msg);

      return false;
    }

    // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
    $library = $messages[0]['context']['library'];
    $libName = $library['name'];
    $libVersion = $library['version'];
    $cmd.= " -H 'User-Agent: ${libName}/${libVersion}'";

    if (!$this->debug()) {
      $cmd .= " > /dev/null 2>&1 &";
    }

    exec($cmd, $output, $exit);

    if (0 != $exit) {
      $this->handleError($exit, $output);
    }

    if ($tmpfname != "") {
      unlink($tmpfname);
    }

    return 0 == $exit;
  }
}
