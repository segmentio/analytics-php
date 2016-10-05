<?php

namespace Segment\Consumer;

interface ConsumerInterface
{
    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param string $secret
     * @param array $options array of consumer options [optional]
     */
    public function __construct($secret, $options = array());

    public function __destruct();

    /**
     * Tracks a user action
     *
     * @param  array $message
     * @return [boolean] whether the track call succeeded
     */
    public function track(array $message);

    /**
     * Tags traits about the user.
     *
     * @param  [array] $message
     * @return [boolean] whether the track call succeeded
     */
    public function identify(array $message);

    /**
     * Tags traits about the group.
     *
     * @param  [array] $message
     * @return [boolean] whether the group call succeeded
     */
    public function group(array $message);

    /**
     * Tracks a page view.
     *
     * @param  [array] $message
     * @return [boolean] whether the page call succeeded
     */
    public function page(array $message);

    /**
     * Tracks a screen view.
     *
     * @param  [array] $message
     * @return [boolean] whether the screen call succeeded
     */
    public function screen(array $message);

    /**
     * Aliases from one user id to another
     *
     * @param  array $message
     * @return boolean whether the alias call succeeded
     */
    public function alias(array $message);

    /**
     * Flush any async consumers
     * @return boolean true if flushed successfully
     */
    public function flush();

}