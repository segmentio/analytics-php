<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

use SegmentIO\Analytics_Client;

class ConsumerSocketTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    $this->client = new Analytics_Client("testsecret",
                                          array("consumer" => "socket"));
  }

  function testTrack() {
    $tracked = $this->client->track("some_user", "Socket PHP Event");
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

  function testShortTimeout() {
    $client = new Analytics_Client("testsecret",
                                   array( "timeout"  => 0.01,
                                          "consumer" => "socket" ));

    $tracked = $client->track("some_user", "Socket PHP Event");
    $this->assertTrue($tracked);

    $identified = $client->identify("some_user");
    $this->assertTrue($identified);
    $client->__destruct();
  }

  function testProductionProblems() {
    $client = new Analytics_Client("x", array(
        "consumer"      => "socket",
        "error_handler" => function () { throw new Exception("Was called"); }));

    # Shouldn't error out without debug on.
    $client->track("some_user", "Socket PHP Event");
    $client->__destruct();
  }

  function testDebugProblems() {

    $options = array(
      "debug"         => true,
      "consumer"      => "socket",
      "error_handler" => function ($errno, $errmsg) {
                            if ($errno != 400)
                              throw new Exception("Response is not 400"); }
    );

    $client = new Analytics_Client("x", $options);

    # Should error out with debug on.
    $client->track("some_user", "Socket PHP Event");
    $client->__destruct();
  }


  function testLargeMessage () {
    $options = array(
      "debug"    => true,
      "consumer" => "socket"
    );

    $client = new Analytics_Client("testsecret", $options);

    $big_property = "";

    for ($i = 0; $i < 10000; $i++) {
      $big_property .= "a";
    }

    $client->track("some_user", "Super large PHP Event", array(
      "big_property" => $big_property
    ));

    $client->__destruct();
  }
}
?>