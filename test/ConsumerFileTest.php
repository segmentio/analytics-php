<?php

require_once(dirname(__FILE__) . "/../lib/Segment/Client.php");

class ConsumerFileTest extends PHPUnit_Framework_TestCase {

  private $client;
  private $filename = "/tmp/analytics.log";

  function setUp() {
    date_default_timezone_set("UTC");
    if (file_exists($this->filename()))
      unlink($this->filename());

    $this->client = new Segment_Client("oq0vdlg7yi",
                          array("consumer" => "file",
                                "filename" => $this->filename));

  }

  function tearDown(){
    if (file_exists($this->filename))
      unlink($this->filename);
  }

  function testTrack() {
    $this->assertTrue($this->client->track(array(
      "userId" => "some-user",
      "event" => "File PHP Event - Microtime",
      "timestamp" => microtime(true),
    )));
    $this->checkWritten("track");
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "userId" => "Calvin",
      "traits" => array(
        "loves_php" => false,
        "type" => "analytics.log",
        "birthday" => time()
      )
    )));
    $this->checkWritten("identify");
  }

  function testGroup(){
    $this->assertTrue($this->client->group(array(
      "userId" => "user-id",
      "groupId" => "group-id",
      "traits" => array(
        "type" => "consumer analytics.log test",
      )
    )));
  }

  function testPage(){
    $this->assertTrue($this->client->page(array(
      "userId" => "user-id",
      "name" => "analytics-php",
      "category" => "analytics.log",
      "properties" => array(
        "url" => "https://a.url/"
      )
    )));
  }

  function testScreen(){
    $this->assertTrue($this->client->screen(array(
      "userId" => "userId",
      "name" => "grand theft auto",
      "category" => "analytics.log",
      "properties" => array()
    )));
  }

  function testAlias () {
    $this->assertTrue($this->client->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
    $this->checkWritten("alias");
  }

  function testSend(){
    for ($i = 0; $i < 200; $i++) {
      $this->client->track(array(
        "userId" => "userId",
        "event" => "event"
      ));
    }
    exec("php --define date.timezone=UTC send.php --secret oq0vdlg7yi --file /tmp/analytics.log", $output);
    $this->assertEquals("sent 200 from 200 requests successfully", trim($output[0]));
    $this->assertFalse(file_exists($this->filename()));
  }

  function testProductionProblems() {
    # Open to a place where we should not have write access.
    $client = new Segment_Client("oq0vdlg7yi",
                          array("consumer" => "file",
                                "filename" => "/dev/xxxxxxx" ));

    $tracked = $client->track(array("userId" => "some-user", "event" => "my event"));
    $this->assertFalse($tracked);
  }

  function checkWritten($type) {
    exec("wc -l " . $this->filename, $output);
    $out = trim($output[0]);
    $this->assertEquals($out, "1 " . $this->filename);
    $str = file_get_contents($this->filename);
    $json = json_decode(trim($str));
    $this->assertEquals($type, $json->type);
    unlink($this->filename);
  }

  function filename(){
    return '/tmp/analytics.log';
  }

}
?>
