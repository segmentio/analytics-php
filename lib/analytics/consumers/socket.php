<?php

class Analytics_SocketConsumer extends Analytics_Consumer {

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
      $options["timeout"] = 0.6;

    parent::__construct($secret, $options);
  }

  /**
   * Tracks a user action
   * @param  string  $user_id    user id string
   * @param  string  $event      name of the event
   * @param  array   $properties properties associated with the event
   * @param  string  $timestamp  iso8601 of the timestamp
   * @return boolean whether the track call succeeded
   */
  public function track($user_id, $event, $properties, $context, $timestamp) {

    $body = array(
      "secret"     => $this->secret,
      "userId"     => $user_id,
      "event"      => $event,
      "properties" => $properties,
      "timestamp"  => $timestamp
    );

    return $this->request("track", $body);
  }

  /**
   * Tags traits about the user.
   * @param  string  $user_id
   * @param  array   $traits
   * @param  string  $timestamp   iso8601 of the timestamp
   * @return boolean whether the track call succeeded
   */
  public function identify($user_id, $traits, $context, $timestamp) {

    $body = array(
      "secret"     => $this->secret,
      "userId"     => $user_id,
      "traits"     => $traits,
      "context"    => $context,
      "timestamp"  => $timestamp
    );

    return $this->request("identify", $body);
  }

  /**
   * Make an async request to our API. Does this by opening up a socket
   * and then writing to it without waiting for the response.
   * @param  string  $type ("track" or "identify")
   * @param  array   $body post body content.
   * @return boolean whether the request succeeded
   */
  private function request($type, $body) {

    $body["type"] = $type;

    $content = json_encode($body);

    $protocol = "ssl";
    $host = "api.segment.io";
    $port = 443;
    $path = "/v1/" . $type;

    $timeout = $this->options['timeout'];

    try {
      # Open our socket to the API Server.
      $socket = fsockopen($protocol . "://" . $host, $port, $errno, $errstr,
                          $timeout);

      # If we couldn't open the socket, handle the error.
      if ($errno != 0) {
        $this->handleError($errno, $errstr);
        return false;
      }

      # Create the request body, and make the request.
      $req = $this->createBody($host, $path, $content);
      return $this->makeRequest($socket, $req);

    } catch (Exception $e) {

      $this->handleError($e->getCode(), $e->getMessage());
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
  private function makeRequest($socket, $req) {

    $success = true;

    # Fire off the request without waiting for a response.
    fwrite($socket, $req);

    if ($this->debug()) {
      $res = $this->parseResponse(fread($socket, 2048));

      if ($res["status"] != "200") {
        $this->handleError($res["status"], $res["message"]);
        $success = false;
      }
    }

    fclose($socket);
    return $success;
  }


  /**
   * Create the body to send as the post request.
   * @param  string $host
   * @param  string $path
   * @param  string $content
   * @return string body
   */
  private function createBody($host, $path, $content) {

    $req = "";
    $req.= "POST " . $path . " HTTP/1.1\r\n";
    $req.= "Host: " . $host . "\r\n";
    $req.= "Content-Type: application/json\r\n";
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
      "status"  => $status[1],
      "message" => $result
    );
  }


  /**
   * On an error, try and call the error handler, if debugging output to
   * error_log as well.
   * @param  string $errno
   * @param  string $errstr
   */
  private function handleError($errno, $errstr) {

    if (isset($this->options['error_handler'])) {
      $handler = $this->options['error_handler'];
      $handler($errno, $errstr);
    }

    if ($this->debug()) {
      error_log("[Analytics][Socket] " . $errstr);
    }
  }
}
?>

