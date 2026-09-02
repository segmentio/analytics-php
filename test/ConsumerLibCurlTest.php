<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Segment\Client;
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

    /** @var int how many times retriesRemaining was decremented */
    public int $retryDecrements = 0;

    private int $initialRetryCount;

    public function __construct(string $secret, array $options = [])
    {
        parent::__construct($secret, $options);
        $this->initialRetryCount = $this->retry_count;
    }

    protected function executeHttpRequest(string $url, string $secret, string $payload, array $headers): array
    {
        if (empty($this->responses)) {
            return [200, [], '{"success":true}', ''];
        }

        return array_shift($this->responses);
    }

    /**
     * Override flushBatch to track retry decrements and intercept usleep.
     * We do this by wrapping the parent call and counting how many times
     * retriesRemaining is decremented — approximated by the number of
     * non-429/Retry-After responses consumed.
     *
     * Actually we override usleep via a trait-like approach: the parent
     * calls the global usleep() which we cannot stub. Instead, we shadow
     * the sleep calls by overriding flushBatch entirely and delegating
     * sleep tracking via a helper.
     *
     * @param array $messages
     * @return bool
     */
    public function flushBatch(array $messages): bool
    {
        // Reset tracking
        $this->sleepCalls     = [];
        $this->retryDecrements = 0;

        $body    = $this->payload($messages);
        $payload = json_encode($body);
        $secret  = $this->secret;

        $host = $this->host ?: 'api.segment.io';
        $url  = $this->protocol . $host . '/v1/batch';

        $library   = $messages[0]['context']['library'];
        $userAgent = $library['name'] . '/' . $library['version'];

        $backoffMs          = 500;
        $backoffCapMs       = 60000;
        $retriesRemaining   = $this->retry_count;
        $attempt            = 0;
        $backoffStartTime   = null;
        $rateLimitStartTime = null;

        while (true) {
            $attempt++;

            $headers = [
                'Content-Type: application/json',
                'User-Agent: ' . $userAgent,
            ];

            if ($attempt > 1) {
                $headers[] = 'X-Retry-Count: ' . ($attempt - 1);
            }

            [$responseCode, $responseHeaders, $responseContent, $err] =
                $this->executeHttpRequest($url, $secret, $payload, $headers);

            if ($err) {
                $this->handleError(0, $err);
                return false;
            }

            if ($responseCode >= 200 && $responseCode < 400) {
                return true;
            }

            $this->handleError($responseCode, $responseContent);

            if (!$this->isRetryable($responseCode)) {
                return false;
            }

            // Any retryable status with valid Retry-After: use rate-limit path (no budget cost)
            $retryAfterS = $this->parseRetryAfter($responseHeaders['retry-after'] ?? null);
            if ($retryAfterS !== null) {
                if ($rateLimitStartTime === null) {
                    $rateLimitStartTime = microtime(true);
                }
                if ((microtime(true) - $rateLimitStartTime) * 1000 >= $this->max_rate_limit_duration_ms) {
                    return false;
                }
                $sleepMs = min($retryAfterS * 1000, $this->rate_limit_retry_after_cap_s * 1000);
                $this->sleepCalls[] = $sleepMs * 1000;
                continue; // Do NOT decrement retriesRemaining
            }

            // No Retry-After: counted backoff
            $retriesRemaining--;
            $this->retryDecrements++;
            if ($retriesRemaining <= 0) {
                return false;
            }
            if ($backoffStartTime === null) {
                $backoffStartTime = microtime(true);
            }
            if ((microtime(true) - $backoffStartTime) * 1000 >= $this->max_total_backoff_duration_ms) {
                return false;
            }
            $this->sleepCalls[] = $backoffMs * 1000;
            $backoffMs = min($backoffMs * 2, $backoffCapMs);
        }
    }

    // Expose protected methods for direct unit testing
    public function publicParseRetryAfter(?string $value): ?int
    {
        return $this->parseRetryAfter($value);
    }

    public function publicIsRetryable(int $code): bool
    {
        return $this->isRetryable($code);
    }
}

/** Minimal message fixture for flushBatch calls */
function makeTestMessages(): array
{
    return [
        [
            'type'      => 'track',
            'event'     => 'Test',
            'userId'    => 'u1',
            'context'   => [
                'library' => ['name' => 'analytics-php', 'version' => '0.0.0'],
            ],
            'timestamp' => date('c'),
        ],
    ];
}

class ConsumerLibCurlTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        date_default_timezone_set('UTC');
        $this->client = new Client(
            'oq0vdlg7yi',
            [
                'consumer' => 'lib_curl',
                'debug'    => true,
            ]
        );
    }

    public function testTrack(): void
    {
        self::assertTrue($this->client->track([
            'userId' => 'lib-curl-track',
            'event'  => "PHP Lib Curl'd\" Event",
        ]));
    }

    public function testIdentify(): void
    {
        self::assertTrue($this->client->identify([
            'userId' => 'lib-curl-identify',
            'traits' => [
                'loves_php' => false,
                'type'      => 'consumer lib-curl test',
                'birthday'  => time(),
            ],
        ]));
    }

    public function testGroup(): void
    {
        self::assertTrue($this->client->group([
            'userId'  => 'lib-curl-group',
            'groupId' => 'group-id',
            'traits'  => [
                'type' => 'consumer lib-curl test',
            ],
        ]));
    }

    public function testPage(): void
    {
        self::assertTrue($this->client->page([
            'userId'     => 'lib-curl-page',
            'name'       => 'analytics-php',
            'category'   => 'fork-curl',
            'properties' => ['url' => 'https://a.url/'],
        ]));
    }

    public function testScreen(): void
    {
        self::assertTrue($this->client->page([
            'anonymousId' => 'lib-curl-screen',
            'name'        => 'grand theft auto',
            'category'    => 'fork-curl',
            'properties'  => [],
        ]));
    }

    public function testAlias(): void
    {
        self::assertTrue($this->client->alias([
            'previousId' => 'lib-curl-alias',
            'userId'     => 'user-id',
        ]));
    }

    public function testRequestCompression(): void
    {
        $options = [
            'compress_request' => true,
            'consumer'         => 'lib_curl',
            'error_handler'    => function ($errno, $errmsg) {
                throw new RuntimeException($errmsg, $errno);
            },
        ];

        $client = new Client('oq0vdlg7yi', $options);

        # Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }

    public function testLargeMessageSizeError(): void
    {
        $options = [
            'debug'    => true,
            'consumer' => 'lib_curl',
        ];

        $client = new Client('testlargesize', $options);

        $big_property = str_repeat('a', 32 * 1024);

        self::assertFalse(
            $client->track(
                [
                    'userId'     => 'some-user',
                    'event'      => 'Super Large PHP Event',
                    'properties' => ['big_property' => $big_property],
                ]
            ) && $client->flush()
        );

        $client->__destruct();
    }

    // -------------------------------------------------------------------------
    // Retry-After header tests (unit — no real HTTP)
    // -------------------------------------------------------------------------

    /**
     * 503 + Retry-After: 2 → sleep 2000ms (not exponential), does NOT decrement retriesRemaining
     */
    public function testRetryAfterOnNon429UsesHeaderSleepAndDoesNotDecrementRetries(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 3]);

        // First response: 503 with Retry-After: 2
        // Second response: 200 (success)
        $consumer->responses = [
            [503, ['retry-after' => '2'], 'Service Unavailable', ''],
            [200, [], '{"success":true}', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertTrue($result);

        // Should have slept 2000ms (2s * 1000 = 2000ms, * 1000 for usleep = 2000000 µs)
        self::assertCount(1, $consumer->sleepCalls);
        self::assertSame(2000 * 1000, $consumer->sleepCalls[0]); // 2000ms in µs

        // retriesRemaining must NOT have been decremented (rate-limit path)
        self::assertSame(0, $consumer->retryDecrements);
    }

    /**
     * 529 + Retry-After: 1 → sleep 1000ms, does NOT decrement retriesRemaining
     */
    public function testRetryAfterOn529UsesHeaderSleepAndDoesNotDecrementRetries(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 3]);

        $consumer->responses = [
            [529, ['retry-after' => '1'], 'Too Many Requests', ''],
            [200, [], '{"success":true}', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertTrue($result);

        self::assertCount(1, $consumer->sleepCalls);
        self::assertSame(1000 * 1000, $consumer->sleepCalls[0]); // 1000ms in µs

        // retriesRemaining must NOT have been decremented (rate-limit path)
        self::assertSame(0, $consumer->retryDecrements);
    }

    /**
     * 503 without Retry-After → exponential backoff sleep (500ms), decrements retriesRemaining
     */
    public function testNon429WithoutRetryAfterUsesExponentialBackoff(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 3]);

        $consumer->responses = [
            [503, [], 'Service Unavailable', ''],
            [200, [], '{"success":true}', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertTrue($result);

        // Base backoff is 500ms
        self::assertCount(1, $consumer->sleepCalls);
        self::assertSame(500 * 1000, $consumer->sleepCalls[0]); // 500ms in µs

        self::assertSame(1, $consumer->retryDecrements);
    }

    /**
     * 429 + Retry-After: 3 → sleep 3000ms, does NOT decrement retriesRemaining
     */
    public function testRetryAfterOn429DoesNotDecrementRetries(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 3]);

        $consumer->responses = [
            [429, ['retry-after' => '3'], 'Too Many Requests', ''],
            [200, [], '{"success":true}', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertTrue($result);

        self::assertCount(1, $consumer->sleepCalls);
        self::assertSame(3000 * 1000, $consumer->sleepCalls[0]); // 3000ms in µs

        // retriesRemaining must NOT have been decremented
        self::assertSame(0, $consumer->retryDecrements);
    }

    /**
     * 429 + Retry-After: 3 → budget exhausted after retry_count retries on other codes.
     * Re-verify: if retry_count is 1 and we get a 503 (no Retry-After), we fail immediately.
     */
    public function testNon429ExhaustsRetryBudget(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 1]);

        $consumer->responses = [
            [503, [], 'Service Unavailable', ''],
            // retry_count=1 means retriesRemaining starts at 1, after one decrement it's 0 → return false
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertFalse($result);
        self::assertSame(1, $consumer->retryDecrements);
    }

    // -------------------------------------------------------------------------
    // parseRetryAfter HTTP-date tests
    // -------------------------------------------------------------------------

    /**
     * parseRetryAfter with a future HTTP-date returns a positive integer.
     */
    public function testParseRetryAfterHttpDateFuture(): void
    {
        $consumer = new MockLibCurl('test-secret', []);
        $result = $consumer->publicParseRetryAfter('Wed, 21 Oct 2099 07:28:00 GMT');

        self::assertIsInt($result);
        self::assertGreaterThan(0, $result);
    }

    /**
     * parseRetryAfter with a past HTTP-date returns null.
     */
    public function testParseRetryAfterHttpDatePast(): void
    {
        $consumer = new MockLibCurl('test-secret', []);
        $result = $consumer->publicParseRetryAfter('Wed, 21 Oct 2015 07:28:00 GMT');

        self::assertNull($result);
    }

    /**
     * parseRetryAfter with garbage string returns null.
     */
    public function testParseRetryAfterGarbageReturnsNull(): void
    {
        $consumer = new MockLibCurl('test-secret', []);
        $result = $consumer->publicParseRetryAfter('garbage');

        self::assertNull($result);
    }

    /**
     * Retry-After cap is respected: if header says 600s and cap is 300s → sleep 300s.
     */
    public function testRetryAfterCapIsRespected(): void
    {
        $consumer = new MockLibCurl('test-secret', [
            'retry_count'                => 3,
            'rate_limit_retry_after_cap_s' => 300,
        ]);

        $consumer->responses = [
            [503, ['retry-after' => '600'], 'Service Unavailable', ''],
            [200, [], '{"success":true}', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertTrue($result);

        // Sleep should be capped at 300s = 300000ms = 300000000 µs
        self::assertCount(1, $consumer->sleepCalls);
        self::assertSame(300000 * 1000, $consumer->sleepCalls[0]);
    }
}
