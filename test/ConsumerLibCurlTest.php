<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Segment\Client;

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

        $client = new Client('x', $options);

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
}
