<?php

require_once __DIR__ . "/../lib/Segment/Client.php";

class ConsumerSocketTest extends PHPUnit_Framework_TestCase
{
  private $client;

  public function setUp()
  {
    date_default_timezone_set("UTC");
    $this->client = new Segment_Client(
      "oq0vdlg7yi",
      array("consumer" => "socket")
    );
  }

  public function testTrack()
  {
    $this->assertTrue($this->client->track(array(
      "userId" => "some-user",
      "event" => "Socket PHP Event",
    )));
  }

  public function testIdentify()
  {
    $this->assertTrue($this->client->identify(array(
      "userId" => "Calvin",
      "traits" => array(
        "loves_php" => false,
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
        "type" => "consumer socket test",
      ),
    )));
  }

  public function testPage()
  {
    $this->assertTrue($this->client->page(array(
      "userId" => "user-id",
      "name" => "analytics-php",
      "category" => "socket",
      "properties" => array(
        "url" => "https://a.url/",
      ),
    )));
  }

  public function testScreen()
  {
    $this->assertTrue($this->client->screen(array(
      "anonymousId" => "anonymousId",
      "name" => "grand theft auto",
      "category" => "socket",
      "properties" => array(),
    )));
  }

  public function testAlias()
  {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "some-socket",
      "userId" => "new-socket",
    )));
  }

  public function testShortTimeout()
  {
    $client = new Segment_Client(
      "oq0vdlg7yi",
      array(
        "timeout" => 0.01,
        "consumer" => "socket",
      )
    );

    $this->assertTrue($client->track(array(
      "userId" => "some-user",
      "event" => "Socket PHP Event",
    )));

    $this->assertTrue($client->identify(array(
      "userId" => "some-user",
      "traits" => array(),
    )));

    $client->__destruct();
  }

  public function testProductionProblems()
  {
    $client = new Segment_Client("x",
      array(
        "consumer" => "socket",
        "error_handler" => function () {
          throw new Exception("Was called");
        },
      )
    );

    // Shouldn't error out without debug on.
    $client->track(array("user_id" => "some-user", "event" => "Production Problems"));
    $client->__destruct();
  }

  public function testDebugProblems()
  {
    $options = array(
      "debug" => true,
      "consumer" => "socket",
      "error_handler" => function ($errno, $errmsg) {
        if (400 != $errno) {
          throw new Exception("Response is not 400");
        }
      },
    );

    $client = new Segment_Client("x", $options);

    // Should error out with debug on.
    $client->track(array("user_id" => "some-user", "event" => "Socket PHP Event"));
    $client->__destruct();
  }

  public function testLargeMessage()
  {
    $options = array(
      "debug" => true,
      "consumer" => "socket",
    );

    $client = new Segment_Client("testsecret", $options);

    $big_property = "";

    for ($i = 0; $i < 10000; ++$i) {
      $big_property .= "a";
    }

    $this->assertTrue($client->track(array(
      "userId" => "some-user",
      "event" => "Super Large PHP Event",
      "properties" => array("big_property" => $big_property),
    )));

    $client->__destruct();
  }

  public function testLargeMessageSizeError()
  {
    $options = array(
      "debug" => true,
      "consumer" => "socket",
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

  /**
   * @expectedException \RuntimeException
   */
  public function testConnectionError()
  {
    $client = new Segment_Client("x", array(
      "consumer" => "socket",
      "host" => "api.segment.ioooooo",
      "error_handler" => function ($errno, $errmsg) {
        throw new \RuntimeException($errmsg, $errno);
      },
    ));

    $client->track(array("user_id" => "some-user", "event" => "Event"));
    $client->__destruct();
  }

  public function testRequestCompression() {
    $options = array(
      "compress_request" => true,
      "consumer"      => "socket",
      "error_handler" => function ($errno, $errmsg) {
        throw new \RuntimeException($errmsg, $errno);
      },
    );

    $client = new Segment_Client("x", $options);

    # Should error out with debug on.
    $client->track(array("user_id" => "some-user", "event" => "Socket PHP Event"));
    $client->__destruct();
  }
}
