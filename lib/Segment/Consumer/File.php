<?php

class Segment_Consumer_File extends Segment_Consumer {
  protected $type = "File";

  private $file_handle;

  /**
   * The file consumer writes track and identify calls to a file.
   * @param string $secret
   * @param array  $options
   *     string "filename" - where to log the analytics calls
   */
  public function __construct($secret, $options = array()) {
    if (!isset($options["filename"])) {
      $options["filename"] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "analytics.log";
    }

    parent::__construct($secret, $options);

    try {
      $this->file_handle = fopen($options["filename"], "a");
      if (isset($options["filepermissions"])) {
          chmod($options["filename"], $options["filepermissions"]);
      } else {
          chmod($options["filename"], 0777);
      }
    } catch (Exception $e) {
      $this->handleError($e->getCode(), $e->getMessage());
    }
  }

  public function __destruct() {
    if ($this->file_handle &&
        "Unknown" != get_resource_type($this->file_handle)) {
      fclose($this->file_handle);
    }
  }

  //define getter method for consumer type
  public function getConsumer() {
    return $this->type;
  }

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return [boolean] whether the track call succeeded
   */
  public function track(array $message) {
    return $this->write($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array $message
   * @return [boolean] whether the identify call succeeded
   */
  public function identify(array $message) {
    return $this->write($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array $message
   * @return [boolean] whether the group call succeeded
   */
  public function group(array $message) {
    return $this->write($message);
  }

  /**
   * Tracks a page view.
   *
   * @param  array $message
   * @return [boolean] whether the page call succeeded
   */
  public function page(array $message) {
    return $this->write($message);
  }

  /**
   * Tracks a screen view.
   *
   * @param  array $message
   * @return [boolean] whether the screen call succeeded
   */
  public function screen(array $message) {
    return $this->write($message);
  }

  /**
   * Aliases from one user id to another
   *
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    return $this->write($message);
  }

  /**
   * Writes the API call to a file as line-delimited json
   * @param  [array]   $body post body content.
   * @return [boolean] whether the request succeeded
   */
  private function write($body) {
    if (!$this->file_handle) {
      return false;
    }

      $content = json_encode($body);
    $content.= "\n";

    return fwrite($this->file_handle, $content) == strlen($content);
  }
}
