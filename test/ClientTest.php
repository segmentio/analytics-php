<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use Segment\Client;
use Segment\Consumer\ForkCurl;
use Segment\Consumer\LibCurl;

class ClientTest extends TestCase
{
    /** @test */
    public function it_uses_the_lib_curl_consumer_as_default(): void
    {
        $client = new Client('foobar', []);
        self::assertInstanceOf(LibCurl::class, $client->getConsumer());
    }

    /** @test */
    public function can_provide_the_consumer_configuration_as_string(): void
    {
        $client = new Client('foobar', ['consumer' => 'fork_curl']);
        self::assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }

    /** @test */
    public function can_provide_a_class_namespace_as_consumer_configuration(): void
    {
        $client = new Client('foobar', [
            'consumer' => ForkCurl::class,
        ]);
        self::assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }
}
