<?php

require_once(dirname(__FILE__) . "/../lib/analytics/client.php");

class SocketConsumerTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    $this->client = new Analytics_Client("testsecret",
                                          array("consumer" => "socket"));
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
    $client = new Analytics_Client("testsecret",
                                   array( "timeout"  => 0.01,
                                          "consumer" => "socket" ));

    $tracked = $client->track("some_user", "Test PHP Event");
    $this->assertFalse($tracked);

    $identified = $client->identify("some_user");
    $this->assertFalse($identified);
  }

  function testProductionProblems() {
    $client = new Analytics_Client("x");

    # Shouldn't error out without debug on.
    $client->track("some_user", "Test PHP Event");
    #$flushed = $client->flush();
    #$this->assertTrue($flushed);
  }

  function testDebugProblems() {
    $client = new Analytics_Client("x", array("debug"    => true,
                                              "consumer" => "socket"));

    # Should error out with debug on.
    $client->track("some_user", "Test PHP Event");
    $client->__destruct();
    #$this->assertFalse($flushed);
  }
}
?>