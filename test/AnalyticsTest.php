<?php

require_once(dirname(__FILE__) . "/../lib/Segment.php");

class AnalyticsTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    date_default_timezone_set("UTC");
    Segment::init("oq0vdlg7yi");
  }

  function testTrack() {
    $this->assertTrue(Segment::track(array(
      "userId" => "john",
      "event" => "Module PHP Event"
    )));
  }

  function testGroup(){
    $this->assertTrue(Segment::group(array(
      "groupId" => "group-id",
      "userId" => "user-id",
      "traits" => array(
        "plan" => "startup"
      )
    )));
  }

  function testPage(){
    $this->assertTrue(Segment::page(array(
      "anonymousId" => "user-id",
      "name" => "analytics-php",
      "category" => "docs",
      "properties" => array(
        "path" => "/docs/libraries/php/",
        "url" => "https://segment.io/docs/libraries/php/"
      )
    )));
  }

  function testScreen(){
    $this->assertTrue(Segment::screen(array(
      "anonymousId" => "anonymous-id",
      "name" => "2048",
      "category" => "game built with php :)",
      "properties" => array(
        "points" => 300
      )
    )));
  }

  function testIdentify() {
    $this->assertTrue(Segment::identify(array(
      "userId" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue(Segment::alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }
}
?>
