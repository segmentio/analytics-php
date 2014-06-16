<?php

require_once(dirname(__FILE__) . "/../lib/Segment/Client.php");

class ConsumerForkCurlTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    date_default_timezone_set("UTC");
    $this->client = new Segment_Client("oq0vdlg7yi",
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
        "type" => "consumer fork-curl test",
        "birthday" => time()
      )
    )));
  }

  function testGroup(){
    $this->assertTrue($this->client->group(array(
      "userId" => "user-id",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer fork-curl test"
      )
    )));
  }

  function testPage(){
    $this->assertTrue($this->client->page(array(
      "userId" => "userId",
      "name" => "analytics-php",
      "category" => "fork-curl",
      "properties" => array(
        "url" => "https://a.url/"
      )
    )));
  }

  function testScreen(){
    $this->assertTrue($this->client->page(array(
      "anonymousId" => "anonymous-id",
      "name" => "grand theft auto",
      "category" => "fork-curl",
      "properties" => array()
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
