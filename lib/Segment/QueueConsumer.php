<?php
abstract class Segment_QueueConsumer extends Segment_Consumer {

  protected $type = "QueueConsumer";

  protected $queue;
  protected $max_queue_size = 1000;
  protected $batch_size = 100;

  /**
   * There is a maximum of 500kb per batch request and 32kb per call.
   *
   * The API will return a 400 response for batch sizes that are over 500kb but
   * will still return 200 if the individual calls are over 32kb. Unfortunately,
   * those calls will silently fail.
   *
   * This option allows PHP to raise an error when request sizes are over the
   * API set limit to avoid silently lost data.
   *
   * @see https://segment.com/docs/sources/server/http/#max-request-size
   */
  protected $check_max_request_size = true;

  const MAX_REQUEST_CALL_SIZE_LIMIT = 32000;
  const MAX_REQUEST_BATCH_SIZE_LIMIT = 500000;

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

    if (isset($options["check_max_request_size"]))
      $this->check_max_request_size = $options["check_max_request_size"];

    $this->queue = array();
  }

  public function __destruct() {
    # Flush our queue on destruction
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
     * Adds an item to our queue.
     * @param  mixed $item
     * @return bool whether call has succeeded
     */
  protected function enqueue($item) {

    $count = count($this->queue);

    if ($count > $this->max_queue_size) {
      return false;
    }

    /**
     * There is a maximum of 500kb per batch request and 32kb per call.
     *
     * If checking for the max request size is enabled, we'll want to ensure
     * that the call payload is less than 32kb.
     *
     * If it is larger than 32kb it will not be added to the queue.
     *
     * @see https://segment.com/docs/sources/server/http/#max-request-size
     */
    if ($this->check_max_request_size) {

        if ($call_size = strlen(json_encode($item)) >= self::MAX_REQUEST_CALL_SIZE_LIMIT ) {
            $this->handleError(400,"The call exceeds batch import limits ($call_size bytes)");
            return false;
        }
    }

    $count = array_push($this->queue, $item);

    if ($count >= $this->batch_size) {
      return $this->flush(); // return ->flush() result: true on success
    }

    return true;
  }


  /**
   * Flushes our queue of messages by batching them to the server
   */
  public function flush() {

    $count = count($this->queue);
    $success = true;
    $batch_size = $this->batch_size;

    while($count > 0 && $success) {

        /*
         * We use array_slice to non destructively get a batch from the queued
         * array of calls. In the event that the batch is too big, we will try
         * with a smaller batch size.
         */
        $batch = array_slice($this->queue, 0, min($batch_size, $count));

        $body = $this->payload($batch);
        $payload = json_encode($body);

        /**
         * There is a maximum of 500kb per batch request and 32kb per call.
         *
         * If checking for the max request size is enabled, we'll want to ensure
         * that the entire payload is less than 500kb.
         *
         * If it is over 500kb, we retry at a smaller batch size until it's under
         * the limit.
         *
         * @see https://segment.com/docs/sources/server/http/#max-request-size
         */
        if ($this->check_max_request_size) {

            if ($payload_size = strlen($payload) >= self::MAX_REQUEST_BATCH_SIZE_LIMIT ) {

                /*
                 * If the payload size is too large with only 1 call in it then
                 * there isn't anything else we can do but report it as an error.
                 */
                if ($batch_size == 1) {

                    $this->handleError(400, "The batch size is over the API limit ($payload_size bytes)");

                    return false;
                }

                /*
                 * Reduce the batch size and try again
                 */
                $batch_size = round($batch_size / 2);
                continue;
            }
        }

      /*
       * Now that we're sure this is the payload we want to use and that it is
       * within the max batch request size limit, we'll remove the calls from
       * the queue using array_splice.
       */
      array_splice($this->queue, 0, min($batch_size, $count));

      $success = $success && $this->flushBatch($payload);

      $count = count($this->queue);
    }

    return $success;
  }

  /**
   * Given a batch of messages the method returns
   * a valid payload.
   *
   * @param {Array} batch
   * @return {Array}
   **/
  protected function payload($batch){
    return array(
      "batch" => $batch,
      "sentAt" => date("c"),
    );
  }

  /**
   * Flushes a batch of messages.
   * @param  string $payload JSON payload of the batch
   * @return [type]        [description]
   */
  abstract function flushBatch($payload);
}
