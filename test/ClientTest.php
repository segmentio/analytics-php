<?php

require_once __DIR__ . '/../lib/Segment/Client.php';

class ClientTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_uses_the_lib_curl_consumer_as_default()
    {
        $client = new Segment_Client('foobar', []);
        $this->assertInstanceOf(Segment_Consumer_LibCurl::class, $client->getConsumer());
    }
    
    /** @test */
    public function can_provide_the_consumer_configuration_as_string()
    {
        $client = new Segment_Client('foobar', [
            'consumer' => 'fork_curl',
        ]);
        $this->assertInstanceOf(Segment_Consumer_ForkCurl::class, $client->getConsumer());
    }
    
    /** @test */
    public function can_provide_a_class_namespace_as_consumer_configuration()
    {
        $client = new Segment_Client('foobar', [
            'consumer' => Segment_Consumer_ForkCurl::class,
        ]);
        $this->assertInstanceOf(Segment_Consumer_ForkCurl::class, $client->getConsumer());
    }
}
