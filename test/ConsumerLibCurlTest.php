<?php

require_once __DIR__ . "/../lib/Segment/Client.php";

class ConsumerLibCurlTest extends PHPUnit_Framework_TestCase
{
  private $client;

  public function setUp()
  {
    date_default_timezone_set("UTC");
    $this->client = new Segment_Client(
      "oq0vdlg7yi",
      array(
        "consumer" => "lib_curl",
        "debug" => true,
      )
    );
  }

  public function testTrack()
  {
    $this->assertTrue($this->client->track(array(
      "userId" => "lib-curl-track",
      "event" => "PHP Lib Curl'd\" Event",
    )));
  }

  public function testIdentify()
  {
    $this->assertTrue($this->client->identify(array(
      "userId" => "lib-curl-identify",
      "traits" => array(
        "loves_php" => false,
        "type" => "consumer lib-curl test",
        "birthday" => time(),
      ),
    )));
  }

  public function testGroup()
  {
    $this->assertTrue($this->client->group(array(
      "userId" => "lib-curl-group",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer lib-curl test",
      ),
    )));
  }

  public function testPage()
  {
    $this->assertTrue($this->client->page(array(
      "userId" => "lib-curl-page",
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
      "anonymousId" => "lib-curl-screen",
      "name" => "grand theft auto",
      "category" => "fork-curl",
      "properties" => array(),
    )));
  }

  public function testAlias()
  {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "lib-curl-alias",
      "userId" => "user-id",
    )));
  }

  public function testRequestCompression() {
    $options = array(
      "compress_request" => true,
      "consumer"      => "lib_curl",
      "error_handler" => function ($errno, $errmsg) {
        throw new \RuntimeException($errmsg, $errno);
      },
    );

    $client = new Segment_Client("x", $options);

    # Should error out with debug on.
    $client->track(array("user_id" => "some-user", "event" => "Socket PHP Event"));
    $client->__destruct();
  }

  public function testLargeMessageSizeError()
  {
    $options = array(
      "debug" => true,
      "consumer" => "lib_curl",
    );

    $client = new Segment_Client("testlargesize", $options);

    $big_property = "";

    for ($i = 0; $i < 32 * 1024; ++$i) {
      $big_property .= "a";
    }

    $this->assertFalse(
      $client->track(
        array(
          "userId" => "some-user",
          "event" => "Super Large PHP Event",
          "properties" => array("big_property" => $big_property),
        )
      ) && $client->flush()
    );

    $client->__destruct();
  }
}
