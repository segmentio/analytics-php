<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Segment\Client;

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
        self::assertSame(0, $consumer->backoffSleeps);
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
        self::assertSame(0, $consumer->backoffSleeps);
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

        self::assertSame(1, $consumer->backoffSleeps);
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
        self::assertSame(0, $consumer->backoffSleeps);
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
        // retry_count = 1, so the single decrement exhausts the budget and the
        // batch is abandoned without ever waiting.
        self::assertSame(0, $consumer->backoffSleeps);
        self::assertCount(0, $consumer->sleepCalls);
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
