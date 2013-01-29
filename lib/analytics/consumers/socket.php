<?php

class Analytics_SocketConsumer extends Analytics_Consumer {

  private $secret;
  private $options;

  /**
   * Creates a new socket consumer for dispatching async requests immediately
   * @param string $secret
   * @param array  $options
   *     @field number   $timeout - the timeout for connecting
   *     @field function error_handler - function called back on errors.
   */
  public function __construct($secret, $options = array()) {

    if (!isset($options["timeout"]))
      $options["timeout"] = 0.4;

    $this->secret  = $secret;
    $this->options = $options;
  }

  /**
   * Tracks a user action
   * @param  [string] $user_id    user id string
   * @param  [string] $event      name of the event
   * @param  [array]  $properties properties associated with the event
   * @param  [string] $timestamp  iso8601 of the timestamp
   * @return [boolean] whether the track call succeeded
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
   * @param  [string] $user_id
   * @param  [array]  $traits
   * @param  [string] $timestamp   iso8601 of the timestamp
   * @return [boolean] whether the track call succeeded
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
   * @param  [string]  $type ("track" or "identify")
   * @param  [array]   $body post body content.
   * @return [boolean] whether the request succeeded
   */
  private function request($type, $body) {

    $body["type"] = $type;

    $content = json_encode($body);

    $protocol = "ssl";
    $host = "api.segment.io";
    $port = 443;
    $path = "/v1/" . $type;

    $timeout = $this->options['timeout'];

    # Open our socket to the API Server.
    $socket = fsockopen($protocol . "://" . $host, $port, $errno, $errstr,
                        $timeout);

    # Create the request body
    if ($errno == 0) {
      $req = "";
      $req.= "POST " . $path . " HTTP/1.1\r\n";
      $req.= "Host: " . $host . "\r\n";
      $req.= "Content-Type: application/json\r\n";
      $req.= "Accept: application/json\r\n";
      $req.= "Content-length: " . strlen($content) . "\r\n";
      $req.= "\r\n";
      $req.= $content;

      # Fire off the request without waiting for a response.
      fwrite($socket, $req);
      fclose($socket);

      return true;
    } else {

      if (isset($this->options['error_handler'])) {
        $error_handler = $this->options['error_handler'];
        $error_handler($errno, $errstr);
      }

      return false;
    }
  }
}
?>

