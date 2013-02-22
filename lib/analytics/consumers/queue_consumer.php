<?php
abstract class Analytics_QueueConsumer extends Analytics_Consumer {

  protected $type = "QueueConsumer";

  protected $queue;
  protected $max_queue_size = 1000;
  protected $batch_size = 100;

  /**
   * Store our secret and options as part of this consumer
   * @param string $secret
   * @param array  $options
   */
  public function __construct($secret, $options = array()) {
    parent::__construct($secret, $options);

    if (isset($options["max_queue_size"]))
      $this->max_queue_size = $options["max_queue_size"];

    if (isset($options["batch_size"]))
      $this->batch_size = $options["batch_size"];

    $this->queue = array();
  }

  public function __destruct() {
    # Flush our queue on destruction
    $this->flush();
  }

  /**
   * Adds an item to our queue.
   * @param  mixed   $item
   * @return boolean whether the queue has room
   */
  protected function enqueue($item) {

    $count = count($this->queue);

    if ($count > $this->max_queue_size)
      return false;

    $count = array_push($this->queue, $item);

    if ($count > $this->batch_size)
      $this->flush();

    return true;
  }


  /**
   * Flushes our queue of messages by batching them to the server
   */
  protected function flush() {

    $count = count($this->queue);
    $success = true;

    while($count > 0 && $success) {

      $batch = array_splice($this->queue, 0, min($this->batch_size, $count));
      $success = $this->flushBatch($batch);

      $count = count($this->queue);
    }

    return $success;
  }

  /**
   * Flushes a batch of messages.
   * @param  [type] $batch [description]
   * @return [type]        [description]
   */
  abstract function flushBatch($batch);
}
