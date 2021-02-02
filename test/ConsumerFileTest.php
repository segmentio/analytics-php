<?php

require_once __DIR__ . "/../lib/Segment/Client.php";

class ConsumerFileTest extends PHPUnit_Framework_TestCase
{
  private $client;
  private $filename = "/tmp/analytics.log";

  public function setUp()
  {
    date_default_timezone_set("UTC");
    if (file_exists($this->filename())) {
      unlink($this->filename());
    }

    $this->client = new Segment_Client(
      "oq0vdlg7yi",
      array(
        "consumer" => "file",
        "filename" => $this->filename,
      )
    );
  }

  public function tearDown()
  {
    if (file_exists($this->filename)) {
      unlink($this->filename);
    }
  }

  public function testTrack()
  {
    $this->assertTrue($this->client->track(array(
      "userId" => "some-user",
      "event" => "File PHP Event - Microtime",
      "timestamp" => microtime(true),
    )));
    $this->checkWritten("track");
  }

  public function testIdentify()
  {
    $this->assertTrue($this->client->identify(array(
      "userId" => "Calvin",
      "traits" => array(
        "loves_php" => false,
        "type" => "analytics.log",
        "birthday" => time(),
      ),
    )));
    $this->checkWritten("identify");
  }

  public function testGroup()
  {
    $this->assertTrue($this->client->group(array(
      "userId" => "user-id",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer analytics.log test",
      ),
    )));
  }

  public function testPage()
  {
    $this->assertTrue($this->client->page(array(
      "userId" => "user-id",
      "name" => "analytics-php",
      "category" => "analytics.log",
      "properties" => array(
        "url" => "https://a.url/",
      ),
    )));
  }

  public function testScreen()
  {
    $this->assertTrue($this->client->screen(array(
      "userId" => "userId",
      "name" => "grand theft auto",
      "category" => "analytics.log",
      "properties" => array(),
    )));
  }

  public function testAlias()
  {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id",
    )));
    $this->checkWritten("alias");
  }

  public function testSend()
  {
    for ($i = 0; $i < 200; ++$i) {
      $this->client->track(array(
        "userId" => "userId",
        "event" => "event",
      ));
    }
    exec("php --define date.timezone=UTC send.php --secret oq0vdlg7yi --file /tmp/analytics.log", $output);
    $this->assertSame("sent 200 from 200 requests successfully", trim($output[0]));
    $this->assertFileNotExists($this->filename());
  }

  public function testProductionProblems()
  {
    // Open to a place where we should not have write access.
    $client = new Segment_Client(
      "oq0vdlg7yi",
      array(
        "consumer" => "file",
        "filename" => "/dev/xxxxxxx",
      )
    );

    $tracked = $client->track(array("userId" => "some-user", "event" => "my event"));
    $this->assertFalse($tracked);
  }

  public function testFileSecurityCustom() {
    $client = new Segment_Client(
      "testsecret",
      array(
        "consumer" => "file",
        "filename" => $this->filename,
        "filepermissions" => 0700
      )
    );
    $tracked = $client->track(array("userId" => "some_user", "event" => "File PHP Event"));
    $this->assertEquals(0700, (fileperms($this->filename) & 0777));
  }

  public function testFileSecurityDefaults() {
    $client = new Segment_Client(
      "testsecret",
      array(
        "consumer" => "file",
        "filename" => $this->filename
      )
    );
    $tracked = $client->track(array("userId" => "some_user", "event" => "File PHP Event"));
    $this->assertEquals(0777, (fileperms($this->filename) & 0777));
  }

  public function checkWritten($type)
  {
    exec("wc -l " . $this->filename, $output);
    $out = trim($output[0]);
    $this->assertSame($out, "1 " . $this->filename);
    $str = file_get_contents($this->filename);
    $json = json_decode(trim($str));
    $this->assertSame($type, $json->type);
    unlink($this->filename);
  }

  public function filename()
  {
    return '/tmp/analytics.log';
  }
}
