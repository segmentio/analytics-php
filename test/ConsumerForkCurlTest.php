<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use Segment\Client;

class ConsumerForkCurlTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        date_default_timezone_set('UTC');
        $this->client = new Client(
            'OnMMoZ6YVozrgSBeZ9FpkC0ixH0ycYZn',
            [
                'consumer' => 'fork_curl',
                'debug'    => true,
            ]
        );
    }

    public function testTrack(): void
    {
        self::assertTrue($this->client->track([
            'userId' => 'some-user',
            'event'  => "PHP Fork Curl'd\" Event",
        ]));
    }

    public function testIdentify(): void
    {
        self::assertTrue($this->client->identify([
            'userId' => 'user-id',
            'traits' => [
                'loves_php' => false,
                'type'      => 'consumer fork-curl test',
                'birthday'  => time(),
            ],
        ]));
    }

    public function testGroup(): void
    {
        self::assertTrue($this->client->group([
            'userId'  => 'user-id',
            'groupId' => 'group-id',
            'traits'  => [
                'type' => 'consumer fork-curl test',
            ],
        ]));
    }

    public function testPage(): void
    {
        self::assertTrue($this->client->page([
            'userId'     => 'userId',
            'name'       => 'analytics-php',
            'category'   => 'fork-curl',
            'properties' => ['url' => 'https://a.url/'],
        ]));
    }

    public function testScreen(): void
    {
        self::assertTrue($this->client->page([
            'anonymousId' => 'anonymous-id',
            'name'        => 'grand theft auto',
            'category'    => 'fork-curl',
            'properties'  => [],
        ]));
    }

    public function testAlias(): void
    {
        self::assertTrue($this->client->alias([
            'previousId' => 'previous-id',
            'userId'     => 'user-id',
        ]));
    }

    public function testRequestCompression(): void
    {
        $options = [
            'compress_request' => true,
            'consumer'         => 'fork_curl',
            'debug'            => true,
        ];

        // Create client and send Track message
        $client = new Client('OnMMoZ6YVozrgSBeZ9FpkC0ixH0ycYZn', $options);
        $result = $client->track([
            'userId' => 'some-user',
            'event'  => "PHP Fork Curl'd\" Event with compression",
        ]);
        $client->__destruct();

        self::assertTrue($result);
    }
}
