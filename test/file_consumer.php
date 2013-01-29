<?php

require_once(dirname(__FILE__) . "/../lib/analytics/client.php");

class FileConsumerTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    $this->client = new Analytics_Client("testsecret",
                          array("Consumer" => "Analytics_FileConsumer"));
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

  function testProductionProblems() {
    # Open to a place where we should not have write access.
    $client = new Analytics_Client("testsecret",
                          array("Consumer" => "Analytics_FileConsumer",
                                "filename" => "/dev/erwerw" ));

    $tracked = $client->track("some_user", "Test PHP Event");
    $this->assertFalse($tracked);
  }

}
?>