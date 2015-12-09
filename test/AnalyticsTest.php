<?php

require_once(dirname(__FILE__) . "/../lib/Segment.php");

class AnalyticsTest extends PHPUnit_Framework_TestCase {
  protected $segment;

  function setUp() {
    date_default_timezone_set("UTC");
    $this->segment = new Segment("oq0vdlg7yi", array("debug" => true));
  }

  function testTrack() {
    $this->assertTrue($this->segment->track(array(
      "userId" => "john",
      "event" => "Module PHP Event"
    )));
  }

  function testGroup(){
    $this->assertTrue($this->segment->group(array(
      "groupId" => "group-id",
      "userId" => "user-id",
      "traits" => array(
        "plan" => "startup"
      )
    )));
  }

  function testMicrotime(){
    $this->assertTrue($this->segment->page(array(
      "anonymousId" => "anonymous-id",
      "name" => "analytics-php-microtime",
      "category" => "docs",
      "timestamp" => microtime(true),
      "properties" => array(
        "path" => "/docs/libraries/php/",
        "url" => "https://segment.io/docs/libraries/php/"
      )
    )));    
  }

  function testPage(){
    $this->assertTrue($this->segment->page(array(
      "anonymousId" => "anonymous-id",
      "name" => "analytics-php",
      "category" => "docs",
      "properties" => array(
        "path" => "/docs/libraries/php/",
        "url" => "https://segment.io/docs/libraries/php/"
      )
    )));
  }

  function testBasicPage(){
    $this->assertTrue($this->segment->page(array(
      "anonymousId" => "anonymous-id"
    )));
  }

  function testScreen(){
    $this->assertTrue($this->segment->screen(array(
      "anonymousId" => "anonymous-id",
      "name" => "2048",
      "category" => "game built with php :)",
      "properties" => array(
        "points" => 300
      )
    )));
  }

  function testBasicScreen(){
    $this->assertTrue($this->segment->screen(array(
      "anonymousId" => "anonymous-id"
    )));
  }

  function testIdentify() {
    $this->assertTrue($this->segment->identify(array(
      "userId" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testEmptyTraits() {
    $this->assertTrue($this->segment->identify(array(
      "userId" => "empty-traits"
    )));

    $this->assertTrue($this->segment->group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits"
    )));
  }

  function testEmptyArrayTraits() {
    $this->assertTrue($this->segment->identify(array(
      "userId" => "empty-traits",
      "traits" => array()
    )));

    $this->assertTrue($this->segment->group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits",
      "traits" => array()
    )));
  }

  function testEmptyProperties() {
    $this->assertTrue($this->segment->track(array(
      "userId" => "user-id",
      "event" => "empty-properties"
    )));

    $this->assertTrue($this->segment->page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id"
    )));
  }

  function testEmptyArrayProperties(){
    $this->assertTrue($this->segment->track(array(
      "userId" => "user-id",
      "event" => "empty-properties",
      "properties" => array()
    )));

    $this->assertTrue($this->segment->page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id",
      "properties" => array()
    )));
  }

  function testAlias() {
    $this->assertTrue($this->segment->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }
}
?>
