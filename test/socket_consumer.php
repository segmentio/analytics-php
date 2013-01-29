<?php

require_once(dirname(__FILE__) . "/../lib/analytics/client.php");

class SocketConsumerTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    $this->client = new Analytics_Client("testsecret",
                                         "Analytics_SocketConsumer");
  }

  function testTrack() {
    $tracked = $this->client->track("some_user", "Test PHP Event");
    $this->assertTrue($tracked);
  }

  function testIdentify() {
    $identified = $this->client->identify("some_user", array(
                    "name"      => "Calvin",
                    "loves_php" => false,
                    "birthday"  => time(),
                    ));

    $this->assertTrue($identified);
  }

  function testShortTimeout() {
    $this->client = new Analytics_Client("testsecret",
                                         "Analytics_SocketConsumer",
                                         array( "timeout" => 0.05 ));

    $tracked = $this->client->track("some_user", "Test PHP Event");
    $this->assertFalse($tracked);

    $identified = $this->client->identify("some_user");
    $this->assertFalse($identified);
  }
}
?>