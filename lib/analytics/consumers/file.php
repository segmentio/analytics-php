<?php

class Analytics_FileConsumer extends Analytics_Consumer {

  private $file_handle;

  /**
   * The file consumer writes track and identify calls to a file.
   * @param string $secret
   * @param array  $options
   *     string "filename" - where to log the analytics calls
   */
  public function __construct($secret, $options = array()) {

    if (!isset($options["filename"]))
      $options["filename"] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "analytics.log";

    parent::__construct($secret, $options);

    try {
      $this->file_handle = fopen($options["filename"], "a");
    } catch (Exception $e) {

      if ($this->debug())
        error_log($this->file_handle);
    }
  }

  public function __destruct() {
    if ($this->file_handle)
      fclose($this->file_handle);
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
      "timestamp"  => $timestamp,
      "action"     => "track"
    );

    return $this->write($body);
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
      "timestamp"  => $timestamp,
      "action"     => "identify"
    );

    return $this->write($body);
  }

  /**
   * Writes the API call to a file as line-delimeted json
   * @param  [array]   $body post body content.
   * @return [boolean] whether the request succeeded
   */
  private function write($body) {

    if (!$this->file_handle)
      return false;

    $content = json_encode($body);
    $content.= "\n";

    return fwrite($this->file_handle, $content) > 0;
  }
}
?>

