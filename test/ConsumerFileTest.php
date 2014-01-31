<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

class ConsumerFileTest extends PHPUnit_Framework_TestCase {

  private $client;
  private $filename = "/tmp/analytics.log";

  function setUp() {

    if (file_exists($this->filename))
      unlink($this->filename);

    $this->client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => $this->filename));
  }

  function testTrack() {
    $tracked = $this->client->track("some_user", "File PHP Event");
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

  function testAlias () {
    $aliased = $this->client->alias("some_user", "new_user");
    $this->assertTrue($aliased);
    $this->checkWritten();
  }

  function testProductionProblems() {
    # Open to a place where we should not have write access.
    $client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => "/dev/xxxxxxx" ));

    $tracked = $client->track("some_user", "File PHP Event");
    $this->assertFalse($tracked);
  }

  function testFileSecurity() {
    $client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => $this->filename,
                                "filepermissions" => 0700 ));

    $tracked = $client->track("some_user", "File PHP Event");
    $this->assertEquals(0700, (fileperms($this->filename) & 0777));
  }

  function testFileSecurityDefaults() {
    $client = new Analytics_Client("testsecret",
                          array("consumer" => "file",
                                "filename" => $this->filename ));

    $tracked = $client->track("some_user", "File PHP Event");
    $this->assertEquals(0777, (fileperms($this->filename) & 0777));
  }

  function checkWritten() {
    exec("wc -l " . $this->filename, $output);
    $this->assertEquals(trim($output[0]), "1 " . $this->filename);
    unlink($this->filename);
  }

}
?>
