<?php

namespace Segment\Consumer;

require_once __DIR__ . '/../lib/Segment/Client.php';

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_uses_the_lib_curl_consumer_as_default()
    {
        $client = new Client('foobar', []);
        $this->assertInstanceOf(LibCurl::class, $client->getConsumer());
    }

    /** @test */
    public function can_provide_the_consumer_configuration_as_string()
    {
        $client = new Client('foobar', [
            'consumer' => 'fork_curl',
        ]);
        $this->assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }

    /** @test */
    public function can_provide_a_class_namespace_as_consumer_configuration()
    {
        $client = new Client('foobar', [
            'consumer' => ForkCurl::class,
        ]);
        $this->assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }
}
