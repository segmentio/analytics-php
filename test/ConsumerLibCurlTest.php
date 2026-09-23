<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Segment\Client;
use Segment\Consumer\QueueConsumer;

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
     * retry_count of N grants exactly N counted-backoff retries.
     *
     * The budget used to be decremented before the exhaustion check, so N performed
     * N-1 and a retry_count of 1 performed none — indistinguishable from 0.
     */
    public function testRetryCountGrantsExactlyThatManyRetries(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 1]);

        $consumer->responses = [
            [503, [], 'Service Unavailable', ''],
            [503, [], 'Service Unavailable', ''],
        ];

        $result = $consumer->flushBatch(makeTestMessages());

        self::assertFalse($result);
        self::assertSame(1, $consumer->backoffSleeps, 'retry_count 1 should grant one retry');
        self::assertCount(1, $consumer->sleepCalls);
    }

    public function testRetryCountOfZeroGrantsNoRetries(): void
    {
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 0]);

        $consumer->responses = [
            [503, [], 'Service Unavailable', ''],
        ];

        self::assertFalse($consumer->flushBatch(makeTestMessages()));
        self::assertSame(0, $consumer->backoffSleeps);
        self::assertCount(0, $consumer->sleepCalls);
    }

    public function testNegativeBudgetOptionsKeepTheDefault(): void
    {
        // A negative value used to be cast straight in, which silently disabled
        // retrying: retriesRemaining started below zero and the duration budget
        // was already exceeded on the first check.
        $consumer = new MockLibCurl('test-secret', [
            'retry_count'                => -5,
            'max_total_backoff_duration' => -1,
        ]);

        $read = function (string $property) use ($consumer) {
            $ref = new \ReflectionProperty(QueueConsumer::class, $property);
            $ref->setAccessible(true);
            return $ref->getValue($consumer);
        };

        self::assertSame(10, $read('retry_count'), 'default retry_count');
        self::assertSame(43200000, $read('max_total_backoff_duration_ms'), 'default 12h budget');
    }

    public function testZeroRetryCountIsAcceptedRatherThanTreatedAsInvalid(): void
    {
        // retry_count 0 means "do not retry" and is deliberate in analytics-python
        // and analytics-ruby, so php accepts it rather than falling back to 10.
        $consumer = new MockLibCurl('test-secret', ['retry_count' => 0]);

        $ref = new \ReflectionProperty(QueueConsumer::class, 'retry_count');
        $ref->setAccessible(true);

        self::assertSame(0, $ref->getValue($consumer));
    }

    public function testRejectsRetryAfterWhoseWeekdayContradictsTheDate(): void
    {
        // PHP's createFromFormat silently rolls a weekday/date mismatch forward to the
        // next matching weekday and reports no warning, so this turned a date in the
        // past into one ~4 days in the future and took the rate-limit path, which
        // spends no retry budget. 20 Sep 2026 was a Sunday, not a Thursday.
        $consumer = new MockLibCurl('test-secret');

        self::assertNull($consumer->publicParseRetryAfter('Thu, 20 Sep 2026 10:49:58 GMT'));
    }

    public function testAcceptsAllThreeRfc7231DateFormats(): void
    {
        // Guards the round-trip check added above against over-rejecting: asctime pads
        // single-digit days with a second space, which a naive comparison would fail.
        $consumer = new MockLibCurl('test-secret');
        $future = new \DateTimeImmutable('+2 hours');

        self::assertSame(
            7200,
            $consumer->publicParseRetryAfter($future->format('D, d M Y H:i:s') . ' GMT'),
            'IMF-fixdate'
        );
        self::assertSame(
            7200,
            $consumer->publicParseRetryAfter($future->format('l, d-M-y H:i:s') . ' GMT'),
            'RFC 850'
        );
        self::assertSame(
            7200,
            $consumer->publicParseRetryAfter(sprintf(
                '%s %s %2d %s',
                $future->format('D'),
                $future->format('M'),
                (int)$future->format('j'),
                $future->format('H:i:s Y')
            )),
            'asctime, double-spaced single-digit day'
        );
    }

    public function testTransportErrorReportsTheRealCurlErrno(): void
    {
        // The refactor dropped curl_errno and passed a literal 0, so every transport
        // failure looked identical to an error_handler branching on the code.
        $reported = [];
        $consumer = new MockLibCurl('test-secret', [
            'error_handler' => function ($code, $msg) use (&$reported) {
                $reported[] = [$code, $msg];
            },
        ]);

        // 28 is CURLE_OPERATION_TIMEDOUT.
        $consumer->responses = [
            [0, [], '', 'Operation timed out after 5000 milliseconds', 28],
        ];

        self::assertFalse($consumer->flushBatch(makeTestMessages()));
        self::assertCount(1, $reported);
        self::assertSame(28, $reported[0][0]);
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
    public function testOversizedBatchDoesNotWedgeTheQueue(): void
    {
        // A single item over 32KB is rejected by enqueue(), so an oversized *batch*
        // is built from many smaller ones: 20 items just under the item limit sum to
        // roughly 600KB, past the 500KB batch limit.
        $consumer = new MockLibCurl('test-secret', ['flush_at' => 20, 'max_queue_size' => 1000]);

        $chunk = str_repeat('x', 30 * 1024);
        $bigMessage = static function (string $payload): array {
            return [
                'type'      => 'track',
                'event'     => $payload,
                'userId'    => 'u1',
                'context'   => ['library' => ['name' => 'analytics-php', 'version' => '0.0.0']],
                'timestamp' => date('c'),
            ];
        };

        for ($i = 0; $i < 19; $i++) {
            self::assertTrue($consumer->track($bigMessage($chunk)));
        }

        // The 20th reaches flush_at, so enqueue() flushes and the batch trips the
        // size guard.
        self::assertFalse(
            $consumer->track($bigMessage($chunk)),
            'the oversized batch should fail this flush'
        );

        // The batch must have left the queue. If it did not, every later flush takes
        // it again and track() returns false forever.
        $consumer->responses = [[200, [], '{"success":true}', '']];
        $consumer->track($bigMessage('small'));

        self::assertTrue(
            $consumer->flush(),
            'queue is wedged: the oversized batch was never removed'
        );
    }

    public function testRetryAfterCapIsRespected(): void
    {
        $consumer = new MockLibCurl('test-secret', [
            'retry_count'                => 3,
            'rate_limit_retry_after_cap' => 300,
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
