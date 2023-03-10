<?php

declare(strict_types=1);

namespace Segment;

use Segment\Consumer\Consumer;
use Segment\Consumer\File;
use Segment\Consumer\ForkCurl;
use Segment\Consumer\LibCurl;
use Segment\Consumer\Socket;

class Client
{
    protected Consumer $consumer;

    /**
     * Create a new analytics object with your app's secret
     * key
     *
     * @param string $secret
     * @param array $options array of consumer options [optional]
     * @param string Consumer constructor to use, libcurl by default.
     *
     */
    public function __construct(string $secret, array $options = [])
    {

        $consumers = [
            'socket'    => Socket::class,
            'file'      => File::class,
            'fork_curl' => ForkCurl::class,
            'lib_curl'  => LibCurl::class,
        ];
        // Use our socket libcurl by default
        $consumer_type = $options['consumer'] ?? 'lib_curl';

        if (!array_key_exists($consumer_type, $consumers) && class_exists($consumer_type)) {
            if (!is_subclass_of($consumer_type, Consumer::class)) {
                throw new SegmentException('Consumers must extend the Segment/Consumer/Consumer abstract class');
            }
            // Try to resolve it by class name
            $this->consumer = new $consumer_type($secret, $options);
            return;
        }

        $Consumer = $consumers[$consumer_type];

        $this->consumer = new $Consumer($secret, $options);
    }

    public function __destruct()
    {
        $this->consumer->__destruct();
    }

    /**
     * Tracks a user action
     *
     * @param array $message
     * @return bool whether the track call succeeded
     */
    public function track(array $message): bool
    {
        $message = $this->message($message, 'properties');
        $message['type'] = 'track';

        return $this->consumer->track($message);
    }

    /**
     * Add common fields to the given `message`
     *
     * @param array $msg
     * @param string $def
     * @return array
     */

    private function message(array $msg, string $def = ''): array
    {
        if ($def && !isset($msg[$def])) {
            $msg[$def] = [];
        }
        if ($def && empty($msg[$def])) {
            $msg[$def] = (object)$msg[$def];
        }

        if (!isset($msg['context'])) {
            $msg['context'] = [];
        }
        $msg['context'] = array_merge($this->getDefaultContext(), $msg['context']);

        if (!isset($msg['timestamp'])) {
            $msg['timestamp'] = null;
        }
        $msg['timestamp'] = $this->formatTime((int)$msg['timestamp']);

        if (!isset($msg['messageId'])) {
            $msg['messageId'] = self::messageId();
        }

        return $msg;
    }

    /**
     * Add the segment.io context to the request
     * @return array additional context
     */
    private function getDefaultContext(): array
    {
        require __DIR__ . '/Version.php';

        global $SEGMENT_VERSION;

        return [
            'library' => [
                'name'     => 'analytics-php',
                'version'  => $SEGMENT_VERSION,
                'consumer' => $this->consumer->getConsumer(),
            ],
        ];
    }

    /**
     * Formats a timestamp by making sure it is set
     * and converting it to iso8601.
     *
     * The timestamp can be time in seconds `time()` or `microtime(true)`.
     * any other input is considered an error and the method will return a new date.
     *
     * Note: php's date() "u" format (for microseconds) has a bug in it
     * it always shows `.000` for microseconds since `date()` only accepts
     * ints, so we have to construct the date ourselves if microtime is passed.
     *
     * @param mixed $timestamp time in seconds (time()) or a time expression string
     */
    private function formatTime($ts)
    {
        if (!$ts) {
            $ts = time();
        }
        if (filter_var($ts, FILTER_VALIDATE_INT) !== false) {
            return date('c', (int)$ts);
        }

        // anything else try to strtotime the date.
        if (filter_var($ts, FILTER_VALIDATE_FLOAT) === false) {
            if (is_string($ts)) {
                return date('c', strtotime($ts));
            }

            return date('c');
        }

        // fix for floatval casting in send.php
        $parts = explode('.', (string)$ts);
        if (!isset($parts[1])) {
            return date('c', (int)$parts[0]);
        }

        $fmt = sprintf('Y-m-d\TH:i:s.%sP', $parts[1]);

        return date($fmt, (int)$parts[0]);
    }

    /**
     * Generate a random messageId.
     *
     * https://gist.github.com/dahnielson/508447#file-uuid-php-L74
     *
     * @return string
     */

    private static function messageId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Tags traits about the user.
     *
     * @param array $message
     * @return bool whether the track call succeeded
     */
    public function identify(array $message): bool
    {
        $message = $this->message($message, 'traits');
        $message['type'] = 'identify';

        return $this->consumer->identify($message);
    }

    /**
     * Tags traits about the group.
     *
     * @param array $message
     * @return bool whether the group call succeeded
     */
    public function group(array $message): bool
    {
        $message = $this->message($message, 'traits');
        $message['type'] = 'group';

        return $this->consumer->group($message);
    }

    /**
     * Tracks a page view.
     *
     * @param array $message
     * @return bool whether the page call succeeded
     */
    public function page(array $message): bool
    {
        $message = $this->message($message, 'properties');
        $message['type'] = 'page';

        return $this->consumer->page($message);
    }

    /**
     * Tracks a screen view.
     *
     * @param array $message
     * @return bool whether the screen call succeeded
     */
    public function screen(array $message): bool
    {
        $message = $this->message($message, 'properties');
        $message['type'] = 'screen';

        return $this->consumer->screen($message);
    }

    /**
     * Aliases from one user id to another
     *
     * @param array $message
     * @return bool whether the alias call succeeded
     */
    public function alias(array $message): bool
    {
        $message = $this->message($message);
        $message['type'] = 'alias';

        return $this->consumer->alias($message);
    }

    /**
     * Flush any async consumers
     * @return bool true if flushed successfully
     */
    public function flush(): bool
    {
        if (method_exists($this->consumer, 'flush')) {
            return $this->consumer->flush();
        }

        return true;
    }

    /**
     * @return Consumer
     */
    public function getConsumer(): Consumer
    {
        return $this->consumer;
    }
}
