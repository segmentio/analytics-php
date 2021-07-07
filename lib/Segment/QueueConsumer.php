<?php

abstract class Segment_QueueConsumer extends Segment_Consumer {
  protected $type = "QueueConsumer";

  protected $queue;
  protected $max_queue_size = 10000;
  protected $max_queue_size_bytes = 33554432; //32M

  protected $flush_at = 100;
  protected $max_batch_size_bytes = 512000; //500kb
  protected $max_item_size_bytes = 32000; // 32kb
  protected $maximum_backoff_duration = 10000;    // Set maximum waiting limit to 10s
  protected $host = "";
  protected $compress_request = false;

  protected $flush_interval_in_mills = 10000; //frequency in milliseconds to send data, default 10

  /**
   * Store our secret and options as part of this consumer
   * @param string $secret
   * @param array  $options
   */
  public function __construct($secret, $options = array()) {
    parent::__construct($secret, $options);

    if (isset($options["max_queue_size"])) {
      $this->max_queue_size = $options["max_queue_size"];
    }

    if (isset($options["batch_size"])) {
      if($options["batch_size"] < 1) {
        $msg = "Batch Size must not be less than 1";
        error_log("[Analytics][" . $this->type . "] " . $msg);
      } else {
        $msg = "WARNING: batch_size option to be depricated soon, please use new option flush_at";
        error_log("[Analytics][" . $this->type . "] " . $msg);
        $this->flush_at = $options["batch_size"];
      }
    }

    if (isset($options["flush_at"])) {
      if($options["flush_at"] < 1) {
        $msg = "Flush at Size must not be less than 1";
        error_log("[Analytics][" . $this->type . "] " . $msg);
      } else {
        $this->flush_at = $options["flush_at"];
      }
    }    

    if (isset($options["host"])) {
      $this->host = $options["host"];
    }

    if (isset($options["compress_request"])) {
      $this->compress_request = json_decode($options["compress_request"]);
    }

    if (isset($options["flush_interval"])) {
      if($options["flush_interval"] < 1000) {
        $msg = "Flush interval must not be less than 1 second";
        error_log("[Analytics][" . $this->type . "] " . $msg);
      } else {
        $this->flush_interval_in_mills = $options["flush_interval"];
      }
    }
    
    $this->queue = array();
  }

  public function __destruct() {
    // Flush our queue on destruction
    $this->flush();
  }

  /**
   * Tracks a user action
   *
   * @param  array  $message
   * @return boolean whether the track call succeeded
   */
  public function track(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public function identify(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public function group(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Tracks a page view.
   *
   * @param  array  $message
   * @return boolean whether the page call succeeded
   */
  public function page(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Tracks a screen view.
   *
   * @param  array  $message
   * @return boolean whether the screen call succeeded
   */
  public function screen(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Aliases from one user id to another
   *
   * @param  array $message
   * @return boolean whether the alias call succeeded
   */
  public function alias(array $message) {
    return $this->enqueue($message);
  }

  /**
   * Flushes our queue of messages by batching them to the server
   */
  public function flush() {
    $count = count($this->queue);
    $success = true;

    while ($count > 0 && $success) {

      $batch = array_splice($this->queue, 0, min($this->flush_at, $count));

      if (mb_strlen(serialize($batch), '8bit') >= $this->max_batch_size_bytes) {
          $msg = "Batch size is larger than 500KB";
          error_log("[Analytics][" . $this->type . "] " . $msg);

          return false;
      }

      $success = $this->flushBatch($batch);

      $count = count($this->queue);

      if($count > 0)
        usleep($this->flush_interval_in_mills * 1000);
    }

    return $success;
  }

  /**
   * Adds an item to our queue.
   * @param  mixed   $item
   * @return boolean whether call has succeeded
   */
  protected function enqueue($item) {
    $count = count($this->queue);

    if ($count > $this->max_queue_size) {
      return false;
    }

    if (mb_strlen(serialize((array)$this->queue), '8bit') >= $this->max_queue_size_bytes) {
        $msg = "Queue size is larger than 32MB";
        error_log("[Analytics][" . $this->type . "] " . $msg);

      return false;
    }

    if (mb_strlen(json_encode($item), '8bit') >= $this->max_item_size_bytes) {
        $msg = "Item size is larger than 32KB";
        error_log("[Analytics][" . $this->type . "] " . $msg);

        return false;
    }

    $count = array_push($this->queue, $item);
 

    if ($count >= $this->flush_at) {
      return $this->flush(); // return ->flush() result: true on success
    }

    return true;
  }

  /**
   * Given a batch of messages the method returns
   * a valid payload.
   *
   * @param {Array} $batch
   * @return {Array}
   */
  protected function payload($batch){
    return array(
      "batch" => $batch,
      "sentAt" => date("c"),
    );
  }
}