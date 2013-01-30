<?php

class Analytics_ForkConsumer extends Analytics_Consumer {

  /**
   * Creates a new fork consumer which forks to curl
   * @param string $secret
   * @param array  $options
   *     boolean  "debug" - whether to use debug output, wait for response.
   */
  public function __construct($secret, $options = array()) {
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
      "context"    => $context,
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
   * Make an async request to our API. Fork a curl process, immediately send
   * to the API. If debug is enabled, we wait for the response.
   * @param  string  $type ("track" or "identify")
   * @param  array   $body post body content.
   * @return boolean whether the request succeeded
   */
  private function request($type, $body) {

    $body["type"] = $type;

    $content = json_encode($body);

    # Replace our single quotes since we are using them in the terminal
    $content = str_replace("'", "'\''", $content);

    $protocol = "https://";
    $host = "api.segment.io";
    $path = "/v1/" . $type;
    $url = $protocol . $host . $path;

    $cmd = "curl -X POST -H 'Content-Type: application/json' -d '" . $content . "' " . "'" . $url . "'";

    if (!$this->debug()) {
      $cmd .= " > /dev/null 2>&1 &";
    }

    exec($cmd, $output, $exit);

    return $exit == 0;
  }
}
?>