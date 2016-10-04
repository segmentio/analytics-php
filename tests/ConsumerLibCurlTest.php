<?php

require_once(dirname(__FILE__) . "/../lib/Segment/Client.php");

class ConsumerLibCurlTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    date_default_timezone_set("UTC");
    $this->client = new Segment_Client("oq0vdlg7yi",
                          array("consumer" => "lib_curl",
                                "debug"    => true));
  }

  function testTrack() {
      $this->assertTrue($this->client->track(array(
        "userId" => "lib-curl-track",
        "event" => "PHP Lib Curl'd\" Event"
      )));
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "userId" => "lib-curl-identify",
      "traits"  => array(
        "loves_php" => false,
        "type" => "consumer lib-curl test",
        "birthday" => time()
      )
    )));
  }

  function testGroup(){
    $this->assertTrue($this->client->group(array(
      "userId" => "lib-curl-group",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer lib-curl test"
      )
    )));
  }

  function testPage(){
    $this->assertTrue($this->client->page(array(
      "userId" => "lib-curl-page",
      "name" => "analytics-php",
      "category" => "fork-curl",
      "properties" => array(
        "url" => "https://a.url/"
      )
    )));
  }

  function testScreen(){
    $this->assertTrue($this->client->page(array(
      "anonymousId" => "lib-curl-screen",
      "name" => "grand theft auto",
      "category" => "fork-curl",
      "properties" => array()
    )));
  }


  function testAlias() {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "lib-curl-alias",
      "userId" => "user-id"
    )));
  }
}

?>
