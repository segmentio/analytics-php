<?php

declare(strict_types=1);

namespace Segment\Test;

use Segment\Consumer\LibCurl;

/**
 * Testable subclass of LibCurl that intercepts HTTP calls and sleep.
 *
 * Inject a queue of responses via $responses. Each entry:
 *   [statusCode, headers (assoc, lower-cased), body, curlError]
 * When the queue is exhausted, returns a 200 success.
 */
class MockLibCurl extends LibCurl
{
    /** @var array<int, array{int, array<string,string>, string, string}> */
    public array $responses = [];

    /** @var int[] microseconds recorded from each usleep call */
    public array $sleepCalls = [];

    /** @var int how many counted-backoff waits were performed */
    public int $backoffSleeps = 0;

    public function __construct(string $secret, array $options = [])
    {
        parent::__construct($secret, $options);
    }

    protected function executeHttpRequest(string $url, string $secret, string $payload, array $headers): array
    {
        if (empty($this->responses)) {
            return [200, [], '{"success":true}', ''];
        }

        return array_shift($this->responses);
    }

    /**
     * Record the retry schedule instead of sleeping. This overrides only the wait,
     * so the tests exercise the real LibCurl::flushBatch rather than a copy of it.
     */
    protected function sleepBeforeRetry(int $milliseconds, bool $rateLimited): void
    {
        $this->sleepCalls[] = $milliseconds * 1000;
        if (!$rateLimited) {
            $this->backoffSleeps++;
        }
    }

    public function publicParseRetryAfter(?string $value): ?int
    {
        return $this->parseRetryAfter($value);
    }

    public function publicIsRetryable(int $code): bool
    {
        return $this->isRetryable($code);
    }
}
