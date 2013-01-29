<?php

require_once(dirname(__FILE__) . "/../lib/analytics/client.php");

class FileConsumerTest extends PHPUnit_Framework_TestCase {

  private $client;
  private $filename = "/tmp/analytics.log";

  function setUp() {

    #if (file_exists($this->filename))
    #  unlink($this->filename);

    $this->client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => $this->filename));
  }

  function testTrack() {
    $tracked = $this->client->track("some_user", "Test PHP Event");
    $this->assertTrue($tracked);
    $this->checkWritten();
  }

  function testIdentify() {
    $identified = $this->client->identify("some_user", array(
                    "name"      => "Calvin",
                    "loves_php" => false,
                    "birthday"  => time(),
                    ));

    $this->assertTrue($identified);
    $this->checkWritten();
  }

  function testProductionProblems() {
    # Open to a place where we should not have write access.
    $client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => "/dev/xxxxxxx" ));

    $tracked = $client->track("some_user", "Test PHP Event");
    $this->assertFalse($tracked);
  }

  function checkWritten() {
    exec("wc -l " . $this->filename, $output);
    $this->assertEquals($output[0], "1 " . $this->filename);
    #unlink($this->filename);
  }

}
?>