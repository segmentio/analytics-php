<?php

abstract class Segment_QueueConsumer extends Segment_Consumer
{
    /**
     * @var string
     */
    protected $type = "QueueConsumer";

    /**
     * @var array
     */
    protected $queue;

    /**
     * @var integer
     */
    protected $max_queue_size = 1000;

    /**
     * @var integer
     */
    protected $batch_size = 100;

    /**
     * Store our secret and options as part of this consumer
     *
     * @param string $secret
     * @param array $options
     */
    public function __construct($secret, $options = array())
    {
        parent::__construct($secret, $options);

        if (isset($options["max_queue_size"])) {
            $this->max_queue_size = $options["max_queue_size"];
        }

        if (isset($options["batch_size"])) {
            $this->batch_size = $options["batch_size"];
        }

        $this->queue = array();
    }

    /**
     * Do a final flush
     */
    public function __destruct()
    {
        # Flush our queue on destruction
        $this->flush();
    }

    /**
     * Tracks a user action
     *
     * @param array $message
     *
     * @return boolean whether the track call succeeded
     */
    public function track(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Tags traits about the user
     *
     * @param array $message
     *
     * @return boolean whether the identify call succeeded
     */
    public function identify(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Tags traits about the group
     *
     * @param array $message
     *
     * @return boolean whether the group call succeeded
     */
    public function group(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Tracks a page view
     *
     * @param array $message
     *
     * @return boolean whether the page call succeeded
     */
    public function page(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Tracks a screen view
     *
     * @param array $message
     *
     * @return boolean whether the screen call succeeded
     */
    public function screen(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Aliases from one user id to another
     *
     * @param array $message
     *
     * @return boolean whether the alias call succeeded
     */
    public function alias(array $message)
    {
        return $this->enqueue($message);
    }

    /**
     * Adds an item to our queue
     *
     * @param mixed $item
     *
     * @return boolean whether call has succeeded
     */
    protected function enqueue($item)
    {

        $count = count($this->queue);

        if ($count > $this->max_queue_size) {
            return false;
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
    public function flush()
    {

        $count = count($this->queue);
        $success = true;

        while ($count > 0 && $success) {

            $batch = array_splice($this->queue, 0, min($this->batch_size, $count));
            $success = $this->flushBatch($batch);

            $count = count($this->queue);
        }

        return $success;
    }

    /**
     * Given a batch of messages the method returns a valid payload
     *
     * @param array $batch
     * 
     * @return array
     **/
    protected function payload($batch)
    {
        return array(
            "batch" => $batch,
            "sentAt" => date("c"),
        );
    }

    /**
     * Flushes a batch of messages
     *
     * @param array $batch
     *
     * @return boolean
     */
    abstract function flushBatch($batch);
}
