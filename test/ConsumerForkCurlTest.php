<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

class ConsumerForkCurlTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {

    $this->client = new Analytics_Client("testsecret",
                          array("consumer" => "fork_curl",
                                "debug"    => true));
  }

  function testTrack() {
    $this->assertTrue($this->client->track(array(
      "user_id" => "some-user",
      "event" => "PHP Fork Curl'd\" Event"
    )));
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "user_id" => "user-id",
      "traits"  => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue($this->client->alias(array(
      "previous_id" => "previous-id",
      "user_id" => "user-id"
    )));
  }
}
?>
