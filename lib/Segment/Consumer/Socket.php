<?php

class Segment_Consumer_Socket extends Segment_QueueConsumer {

  protected $type = "Socket";
  private $socket_failed;

  //define getter method for consumer type
  public function getConsumer() {
    return $this->type;
  }

  /**
   * Creates a new socket consumer for dispatching async requests immediately
   * @param string $secret
   * @param array  $options
   *     number   "timeout" - the timeout for connecting
   *     function "error_handler" - function called back on errors.
   *     boolean  "debug" - whether to use debug output, wait for response.
   */
  public function __construct($secret, $options = array()) {

    if (!isset($options["timeout"]))
      $options["timeout"] = 5;

    if (!isset($options["host"]))
      $options["host"] = "api.segment.io";

    parent::__construct($secret, $options);
  }


  public function flushBatch($batch) {
    $socket = $this->createSocket();

    if (!$socket)
      return;

    $payload = $this->payload($batch);
    $payload = json_encode($payload);

    $body = $this->createBody($this->options["host"], $payload);
    return $this->makeRequest($socket, $body);
  }


  private function createSocket() {

    if ($this->socket_failed)
      return false;

    $protocol = $this->ssl() ? "ssl" : "tcp";
    $host = $this->options["host"];
    $port = $this->ssl() ? 443 : 80;
    $timeout = $this->options["timeout"];

    try {
      # Open our socket to the API Server.
      # Since we're try catch'ing prevent PHP logs.
      $socket = @pfsockopen($protocol . "://" . $host, $port, $errno,
                           $errstr, $timeout);

      # If we couldn't open the socket, handle the error.
      if (false === $socket) {
        $this->handleError($errno, $errstr);
        $this->socket_failed = true;
        return false;
      }

      return $socket;

    } catch (Exception $e) {
      $this->handleError($e->getCode(), $e->getMessage());
      $this->socket_failed = true;
      return false;
    }
  }

  /**
   * Attempt to write the request to the socket, wait for response if debug
   * mode is enabled.
   * @param  stream  $socket the handle for the socket
   * @param  string  $req    request body
   * @return boolean $success
   */
  private function makeRequest($socket, $req, $retry = true) {

    $bytes_written = 0;
    $bytes_total = strlen($req);
    $closed = false;

    # Write the request
    while (!$closed && $bytes_written < $bytes_total) {
      try {
        # Since we're try catch'ing prevent PHP logs.
        $written = @fwrite($socket, substr($req, $bytes_written));
      } catch (Exception $e) {
        $this->handleError($e->getCode(), $e->getMessage());
        $closed = true;
      }
      if (!isset($written) || !$written) {
        $closed = true;
      } else {
        $bytes_written += $written;
      }
    }

    # If the socket has been closed, attempt to retry a single time.
    if ($closed) {
      fclose($socket);

      if ($retry) {
        $socket = $this->createSocket();
        if ($socket) return $this->makeRequest($socket, $req, false);
      }
      return false;
    }


    $success = true;

    if ($this->debug()) {
      $res = $this->parseResponse(fread($socket, 2048));

      if ($res["status"] != "200") {
        $this->handleError($res["status"], $res["message"]);
        $success = false;
      }
    } else {
      // we have to read from the socket to avoid getting into
      // states where the socket doesn't properly re-open.
      // as long as we keep the recv buffer empty, php will
      // properly reconnect
      stream_set_timeout($socket, 0, 50000);
      fread($socket, 2048);
      stream_set_timeout($socket, 5);
    }

    return $success;
  }


  /**
   * Create the body to send as the post request.
   * @param  string $host
   * @param  string $content
   * @return string body
   */
  private function createBody($host, $content) {

    $req = "";
    $req.= "POST /v1/import HTTP/1.1\r\n";
    $req.= "Host: " . $host . "\r\n";
    $req.= "Content-Type: application/json\r\n";
    $req.= "Authorization: Basic " . base64_encode($this->secret . ":") . "\r\n";
    $req.= "Accept: application/json\r\n";
    $req.= "Content-length: " . strlen($content) . "\r\n";
    $req.= "\r\n";
    $req.= $content;

    return $req;
  }


  /**
   * Parse our response from the server, check header and body.
   * @param  string $res
   * @return array
   *     string $status  HTTP code, e.g. "200"
   *     string $message JSON response from the api
   */
  private function parseResponse($res) {

    $contents = explode("\n", $res);

    # Response comes back as HTTP/1.1 200 OK
    # Final line contains HTTP response.
    $status = explode(" ", $contents[0], 3);
    $result = $contents[count($contents) - 1];

    return array(
      "status"  => isset($status[1]) ? $status[1] : null,
      "message" => $result
    );
  }
}
