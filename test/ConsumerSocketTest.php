<?php

declare(strict_types=1);

namespace Segment\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Segment\Client;

class ConsumerSocketTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        date_default_timezone_set('UTC');
        $this->client = new Client(
            'oq0vdlg7yi',
            ['consumer' => 'socket']
        );
    }

    public function testTrack(): void
    {
        self::assertTrue(
            $this->client->track(
                [
                    'userId' => 'some-user',
                    'event'  => 'Socket PHP Event',
                ]
            )
        );
    }

    public function testIdentify(): void
    {
        self::assertTrue(
            $this->client->identify(
                [
                    'userId' => 'Calvin',
                    'traits' => [
                        'loves_php' => false,
                        'birthday'  => time(),
                    ],
                ]
            )
        );
    }

    public function testGroup(): void
    {
        self::assertTrue(
            $this->client->group(
                [
                    'userId'  => 'user-id',
                    'groupId' => 'group-id',
                    'traits'  => [
                        'type' => 'consumer socket test',
                    ],
                ]
            )
        );
    }

    public function testPage(): void
    {
        self::assertTrue(
            $this->client->page(
                [
                    'userId'     => 'user-id',
                    'name'       => 'analytics-php',
                    'category'   => 'socket',
                    'properties' => ['url' => 'https://a.url/'],
                ]
            )
        );
    }

    public function testScreen(): void
    {
        self::assertTrue(
            $this->client->screen(
                [
                    'anonymousId' => 'anonymousId',
                    'name'        => 'grand theft auto',
                    'category'    => 'socket',
                    'properties'  => [],
                ]
            )
        );
    }

    public function testAlias(): void
    {
        self::assertTrue(
            $this->client->alias(
                [
                    'previousId' => 'some-socket',
                    'userId'     => 'new-socket',
                ]
            )
        );
    }

    public function testShortTimeout(): void
    {
        $client = new Client(
            'oq0vdlg7yi',
            [
                'timeout'  => 0.01,
                'consumer' => 'socket',
            ]
        );

        self::assertTrue(
            $client->track(
                [
                    'userId' => 'some-user',
                    'event'  => 'Socket PHP Event',
                ]
            )
        );

        self::assertTrue(
            $client->identify(
                [
                    'userId' => 'some-user',
                    'traits' => [],
                ]
            )
        );

        $client->__destruct();
    }

    public function testProductionProblems(): void
    {
        $client = new Client(
            'x',
            [
                'consumer'      => 'socket',
                'error_handler' => function () {
                    throw new Exception('Was called');
                },
            ]
        );

        // Shouldn't error out without debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Production Problems']));
        $client->__destruct();
    }

    public function testDebugProblems(): void
    {
        $options = [
            'debug'         => true,
            'consumer'      => 'socket',
            'error_handler' => function ($errno, $errmsg) {
                if ($errno !== 400) {
                    throw new Exception('Response is not 400');
                }
            },
        ];

        $client = new Client('x', $options);

        // Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }

    public function testLargeMessage(): void
    {
        $options = [
            'debug'    => true,
            'consumer' => 'socket',
        ];

        $client = new Client('testsecret', $options);

        $big_property = str_repeat('a', 10000);

        self::assertTrue(
            $client->track(
                [
                    'userId'     => 'some-user',
                    'event'      => 'Super Large PHP Event',
                    'properties' => ['big_property' => $big_property],
                ]
            )
        );

        $client->__destruct();
    }

    public function testLargeMessageSizeError(): void
    {
        $options = [
            'debug'    => true,
            'consumer' => 'socket',
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

    public function testConnectionError(): void
    {
        $this->expectException(RuntimeException::class);
        $client = new Client(
            'x',
            [
                'consumer'      => 'socket',
                'host'          => 'api.segment.ioooooo',
                'error_handler' => function ($errno, $errmsg) {
                    throw new RuntimeException($errmsg, $errno);
                },
            ]
        );

        $client->track(['user_id' => 'some-user', 'event' => 'Event']);
        $client->__destruct();
    }

    public function testRequestCompression(): void
    {
        $options = [
            'compress_request' => true,
            'consumer'         => 'socket',
            'error_handler'    => function ($errno, $errmsg) {
                throw new RuntimeException($errmsg, $errno);
            },
        ];

        $client = new Client('x', $options);

        # Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }
}
