<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

use SegmentIO\Analytics_Client;

class ConsumerForkCurlTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {

    $this->client = new Analytics_Client("testsecret",
                          array("consumer" => "fork_curl",
                                "debug"    => true));
  }

  function testTrack() {
    $tracked = $this->client->track("some_user", "PHP Fork Curl'd\" Event");
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

  function testAlias() {
    $aliased = $this->client->alias("some_user", "new_user");
    $this->assertTrue($aliased);
  }
}
?>