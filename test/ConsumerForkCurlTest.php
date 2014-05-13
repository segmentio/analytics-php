<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

class ConsumerForkCurlTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {

    $this->client = new Analytics_Client("oq0vdlg7yi",
                          array("consumer" => "fork_curl",
                                "debug"    => true));
  }

  function testTrack() {
    $this->assertTrue($this->client->track(array(
      "userId" => "some-user",
      "event" => "PHP Fork Curl'd\" Event"
    )));
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "userId" => "user-id",
      "traits"  => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }
}
?>
