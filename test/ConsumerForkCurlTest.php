<?php

require_once __DIR__ . "/../lib/Segment/Client.php";

class ConsumerForkCurlTest extends PHPUnit_Framework_TestCase
{
  private $client;

  public function setUp()
  {
    date_default_timezone_set("UTC");
    $this->client = new Segment_Client(
      "OnMMoZ6YVozrgSBeZ9FpkC0ixH0ycYZn",
      array(
        "consumer" => "fork_curl",
        "debug" => true,
      )
    );
  }

  public function testTrack()
  {
    $this->assertTrue($this->client->track(array(
      "userId" => "some-user",
      "event" => "PHP Fork Curl'd\" Event",
    )));
  }

  public function testIdentify()
  {
    $this->assertTrue($this->client->identify(array(
      "userId" => "user-id",
      "traits" => array(
        "loves_php" => false,
        "type" => "consumer fork-curl test",
        "birthday" => time(),
      ),
    )));
  }

  public function testGroup()
  {
    $this->assertTrue($this->client->group(array(
      "userId" => "user-id",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer fork-curl test",
      ),
    )));
  }

  public function testPage()
  {
    $this->assertTrue($this->client->page(array(
      "userId" => "userId",
      "name" => "analytics-php",
      "category" => "fork-curl",
      "properties" => array(
        "url" => "https://a.url/",
      ),
    )));
  }

  public function testScreen()
  {
    $this->assertTrue($this->client->page(array(
      "anonymousId" => "anonymous-id",
      "name" => "grand theft auto",
      "category" => "fork-curl",
      "properties" => array(),
    )));
  }

  public function testAlias()
  {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id",
    )));
  }

  public function testRequestCompression() {
    $options = array(
      "compress_request" => true,
      "consumer" => "fork_curl",
      "debug" => true,
    );

    // Create client and send Track message
    $client = new Segment_Client("OnMMoZ6YVozrgSBeZ9FpkC0ixH0ycYZn", $options);
    $result = $client->track(array(
      "userId" => "some-user",
      "event" => "PHP Fork Curl'd\" Event with compression",
    ));
    $client->__destruct();

    $this->assertTrue($result);
  }
}
