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

  function testGroup(){
    $this->assertTrue(Analytics::group(array(
      "groupId" => "group-id",
      "userId" => "user-id",
      "traits" => array(
        "plan" => "startup"
      )
    )));
  }

  function testPage(){
    $this->assertTrue(Analytics::page(array(
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
    $this->assertTrue(Analytics::screen(array(
      "anonymousId" => "anonymous-id",
      "name" => "2048",
      "category" => "game built with php :)",
      "properties" => array(
        "points" => 300
      )
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
