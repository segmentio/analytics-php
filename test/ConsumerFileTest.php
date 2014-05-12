<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

class ConsumerFileTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {

    if (file_exists($this->filename()))
      unlink($this->filename());

    $this->client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => $this->filename()));

  }

  function tearDown(){
    if (file_exists($this->filename()))
      unlink($this->filename());    
  }

  function testTrack() {
    $this->assertTrue($this->client->track(array(
      "user_id" => "some-user",
      "event" => "File PHP Event"
    )));
    $this->checkWritten("track");
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "user_id" => "Calvin",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
    $this->checkWritten("identify");
  }

  function testAlias () {
    $this->assertTrue($this->client->alias(array(
      "previous_id" => "previous-id",
      "user_id" => "user-id"
    )));
    $this->checkWritten("alias");
  }

  function testProductionProblems() {
    # Open to a place where we should not have write access.
    $client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => "/dev/xxxxxxx" ));

    $tracked = $client->track("some_user", "File PHP Event");
    $this->assertFalse($tracked);
  }

  function checkWritten($type) {
    exec("wc -l " . $this->filename(), $output);
    $out = trim($output[0]);
    $this->assertEquals($out, "1 " . $this->filename());
    $str = file_get_contents($this->filename());
    $json = json_decode(trim($str));
    $this->assertEquals($type, $json->type);
    unlink($this->filename());
  }

  function filename(){
    return dirname(__FILE__) . '/analytics.log';
  }

}
?>
