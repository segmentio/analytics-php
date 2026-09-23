<?php

declare(strict_types=1);

namespace Segment\Consumer;

abstract class QueueConsumer extends Consumer
{
    protected string $type = 'QueueConsumer';
    protected string $protocol = 'https://';

    /**
     * @var array<int,mixed>
     */
    protected array $queue;
    protected int $max_queue_size = 10000;
    protected int $max_queue_size_bytes = 33554432; //32M
    protected int $flush_at = 100;
    protected int $max_batch_size_bytes = 512000; //500kb
    protected int $max_item_size_bytes = 32000; // 32kb
    protected int $maximum_backoff_duration = 10000; // Set maximum waiting limit to 10s
    protected int $max_total_backoff_duration_ms = 43200000; // 12 hours
    protected int $max_rate_limit_duration_ms    = 43200000; // 12 hours
    protected int $rate_limit_retry_after_cap_s  = 300;      // 5 minutes
    protected int $retry_count                   = 10;       // max retries
    protected string $host = '';
    protected bool $compress_request = false;
    protected int $flush_interval_in_mills = 10000; //frequency in milliseconds to send data, default 10
    protected int $curl_timeout = 0; // by default this is infinite
    protected int $curl_connecttimeout = 300;

    /**
     * Store our secret and options as part of this consumer
     * @param string $secret
     * @param array $options
     */
    public function __construct(string $secret, array $options = [])
    {
        parent::__construct($secret, $options);

        if (isset($options['max_queue_size'])) {
            $this->max_queue_size = $options['max_queue_size'];
        }

        if (isset($options['batch_size'])) {
            if ($options['batch_size'] < 1) {
                $msg = 'Batch Size must not be less than 1';
                error_log('[Analytics][' . $this->type . '] ' . $msg);
            } else {
                $msg = 'WARNING: batch_size option to be deprecated soon, please use new option flush_at';
                error_log('[Analytics][' . $this->type . '] ' . $msg);
                $this->flush_at = $options['batch_size'];
            }
        }

        if (isset($options['flush_at'])) {
            if ($options['flush_at'] < 1) {
                $msg = 'Flush at Size must not be less than 1';
                error_log('[Analytics][' . $this->type . '] ' . $msg);
            } else {
                $this->flush_at = $options['flush_at'];
            }
        }

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['compress_request'])) {
            $this->compress_request = (bool)$options['compress_request'];
        }

        if (isset($options['flush_interval'])) {
            if ($options['flush_interval'] < 1000) {
                $msg = 'Flush interval must not be less than 1 second';
                error_log('[Analytics][' . $this->type . '] ' . $msg);
            } else {
                $this->flush_interval_in_mills = $options['flush_interval'];
            }
        }

        if (isset($options['curl_timeout'])) {
            $this->curl_timeout = $options['curl_timeout'];
        }

        if (isset($options['curl_connecttimeout'])) {
            $this->curl_connecttimeout = $options['curl_connecttimeout'];
        }

        // These three are in SECONDS, matching the options of the same names in the
        // python, ruby, go and java clients. The _ms fields behind them are internal;
        // taking milliseconds here made 43200 mean 43 seconds rather than 12 hours.
        //
        // Negatives are rejected rather than cast blindly: they silently disabled
        // retrying altogether, the opposite of what someone setting these is asking
        // for. Zero is allowed and meaningful — retry_count 0 means "do not retry",
        // matching analytics-python and analytics-ruby. Bad values log and keep the
        // default, the way flush_at and flush_interval above do.
        if (isset($options['max_total_backoff_duration'])) {
            if ($this->isNonNegativeInt($options['max_total_backoff_duration'], 'max_total_backoff_duration')) {
                $this->max_total_backoff_duration_ms = (int)$options['max_total_backoff_duration'] * 1000;
            }
        }

        if (isset($options['max_rate_limit_duration'])) {
            if ($this->isNonNegativeInt($options['max_rate_limit_duration'], 'max_rate_limit_duration')) {
                $this->max_rate_limit_duration_ms = (int)$options['max_rate_limit_duration'] * 1000;
            }
        }

        if (isset($options['rate_limit_retry_after_cap'])) {
            if ($this->isNonNegativeInt($options['rate_limit_retry_after_cap'], 'rate_limit_retry_after_cap')) {
                $this->rate_limit_retry_after_cap_s = (int)$options['rate_limit_retry_after_cap'];
            }
        }

        if (isset($options['retry_count'])) {
            if ($this->isNonNegativeInt($options['retry_count'], 'retry_count')) {
                $this->retry_count = (int)$options['retry_count'];
            }
        }

        $this->queue = [];
    }

    public function __destruct()
    {
        // Flush our queue on destruction
        $this->flush();
    }

    /**
     * Flushes our queue of messages by batching them to the server
     */
    public function flush(): bool
    {
        $count = count($this->queue);
        $success = true;

        while ($count > 0 && $success) {
            // Remove the batch before doing anything else. Leaving it in place on the
            // oversize bail below would wedge the queue: every later flush would take
            // the same batch, fail the same check, and track() would return false
            // forever.
            $batch = array_splice($this->queue, 0, min($this->flush_at, $count));

            if (mb_strlen(serialize($batch), '8bit') >= $this->max_batch_size_bytes) {
                $msg = 'Batch size is larger than 500KB';
                error_log('[Analytics][' . $this->type . '] ' . $msg);

                return false;
            }

            $success = $this->flushBatch($batch);

            $count = count($this->queue);

            if ($count > 0 && $success) {
                usleep($this->flush_interval_in_mills * 1000);
            }
        }

        return $success;
    }

    /**
     * Whether an option value is usable as a count or duration.
     *
     * Logs and returns false otherwise, so the caller keeps the default. Zero is
     * accepted: analytics-python validates these the same way, and retry_count 0
     * meaning "do not retry" is deliberate there and in analytics-ruby.
     */
    protected function isNonNegativeInt($value, string $name): bool
    {
        if (!is_numeric($value) || (int)$value < 0) {
            error_log(sprintf(
                '[Analytics][%s] %s must be a non-negative integer; keeping the default',
                $this->type,
                $name
            ));
            return false;
        }

        return true;
    }

    /**
     * Determine if a status code is retryable per e2e spec.
     * 5xx are retryable except 501, 505, 511.
     * 4xx are non-retryable except 408, 410, 429, 460.
     */
    protected function isRetryable(int $statusCode): bool
    {
        if ($statusCode >= 500 && $statusCode < 600) {
            return !in_array($statusCode, [501, 505, 511], true);
        }

        return in_array($statusCode, [408, 410, 429, 460], true);
    }

    /** The three date formats RFC 7231 permits for Retry-After. */
    private const HTTP_DATE_FORMATS = [
        'D, d M Y H:i:s \G\M\T',  // IMF-fixdate
        'l, d-M-y H:i:s \G\M\T',  // obsolete RFC 850
        'D M j H:i:s Y',           // obsolete asctime
    ];

    /**
     * Parse Retry-After header as integer seconds.
     * Supports both integer seconds and HTTP-date format (RFC 7231).
     * Returns null if absent, unparseable, zero, or negative.
     */
    protected function parseRetryAfter(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);

        // Try integer seconds
        if (ctype_digit($value)) {
            $seconds = (int)$value;
            return $seconds > 0 ? $seconds : null;
        }

        // Try HTTP-date (RFC 7231 section 7.1.1.1). Parsed strictly rather than with
        // strtotime(), which reads "-1" as a timezone offset and "tomorrow" as a date.
        // A malformed header must not reach the rate-limit path, which spends no
        // retry budget.
        foreach (self::HTTP_DATE_FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value, new \DateTimeZone('UTC'));
            if ($date === false) {
                continue;
            }

            // getLastErrors() returns false when the parse was clean and an array
            // when it was not, so this rejects values createFromFormat accepts with
            // warnings — "Wed, 32 Oct 2099" rolling over into November, for instance.
            $errors = \DateTimeImmutable::getLastErrors();
            if (!empty($errors['warning_count']) || !empty($errors['error_count'])) {
                continue;
            }

            // createFromFormat does not check the day-name token against the rest of
            // the date: on a mismatch it silently rolls the result forward to the next
            // matching weekday and reports no warning, so "Thu, 20 Sep 2026" — actually
            // a Sunday — parses as 24 Sep, turning a date in the past into one in the
            // future. Comparing the parsed date's own weekday cannot catch this, since
            // the roll-forward is what makes the two agree; re-formatting the whole
            // value and comparing does. Whitespace is collapsed so asctime's
            // double-spaced single-digit days still round-trip.
            if (strcasecmp(self::collapseWhitespace($date->format($format)), self::collapseWhitespace($value)) !== 0) {
                continue;
            }

            $seconds = $date->getTimestamp() - time();
            return $seconds > 0 ? $seconds : null;
        }

        return null;
    }

    private static function collapseWhitespace(string $value): string
    {
        return trim((string)preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Tracks a user action
     *
     * @param array $message
     * @return bool whether the track call succeeded
     */
    public function track(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Adds an item to our queue.
     * @param mixed $item
     * @return bool whether call has succeeded
     */
    protected function enqueue($item): bool
    {
        $count = count($this->queue);

        if ($count > $this->max_queue_size) {
            return false;
        }

        if (mb_strlen(serialize($this->queue), '8bit') >= $this->max_queue_size_bytes) {
            $msg = 'Queue size is larger than 32MB';
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

        if (mb_strlen(json_encode($item), '8bit') >= $this->max_item_size_bytes) {
            $msg = 'Item size is larger than 32KB';
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

        $count = array_push($this->queue, $item);

        if ($count >= $this->flush_at) {
            return $this->flush();
        }

        return true;
    }

    /**
     * Tags traits about the user.
     *
     * @param array $message
     * @return bool whether the identify call succeeded
     */
    public function identify(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Tags traits about the group.
     *
     * @param array $message
     * @return bool whether the group call succeeded
     */
    public function group(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Tracks a page view.
     *
     * @param array $message
     * @return bool whether the page call succeeded
     */
    public function page(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Tracks a screen view.
     *
     * @param array $message
     * @return bool whether the screen call succeeded
     */
    public function screen(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Aliases from one user id to another
     *
     * @param array $message
     * @return bool whether the alias call succeeded
     */
    public function alias(array $message): bool
    {
        return $this->enqueue($message);
    }

    /**
     * Given a batch of messages the method returns
     * a valid payload.
     *
     * @param array $batch
     * @return array
     */
    protected function payload(array $batch): array
    {
        return [
            'batch'  => $batch,
            'sentAt' => date('c'),
        ];
    }
}
