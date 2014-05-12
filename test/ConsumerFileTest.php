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
    $tracked = $this->client->track("some_user", "File PHP Event");
    $this->assertTrue($tracked);
    $this->checkWritten("track");
  }

  function testIdentify() {
    $identified = $this->client->identify("some_user", array(
                    "name"      => "Calvin",
                    "loves_php" => false,
                    "birthday"  => time(),
                    ));

    $this->assertTrue($identified);
    $this->checkWritten("identify");
  }

  function testAlias () {
    $aliased = $this->client->alias("some_user", "new_user");
    $this->assertTrue($aliased);
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
    $this->assertEquals($type, $json->action);
    unlink($this->filename());
  }

  function filename(){
    return dirname(__FILE__) . '/analytics.log';
  }

}
?>
