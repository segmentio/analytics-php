<?php

require_once(dirname(__FILE__) . "/../lib/Analytics.php");

class AnalyticsTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    Analytics::init("testsecret");
  }

  function testTrack() {
    $this->assertTrue(Analytics::track(array(
      "user_id" => "john",
      "event" => "Module PHP Event"
    )));
  }

  function testIdentify() {
    $this->assertTrue(Analytics::identify(array(
      "user_id" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue(Analytics::alias(array(
      "previous_id" => "previous-id",
      "user_id" => "user-id"
    )));
  }
}
?>
