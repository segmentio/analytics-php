<?php

require_once(dirname(__FILE__) . "/../lib/Analytics.php");

class AnalyticsTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    Analytics::init("oq0vdlg7yi");
  }

  function testTrack() {
    $this->assertTrue(Analytics::track(array(
      "userId" => "john",
      "event" => "Module PHP Event"
    )));
  }

  function testIdentify() {
    $this->assertTrue(Analytics::identify(array(
      "userId" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue(Analytics::alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }
}
?>
