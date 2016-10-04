<?php

namespace SegmentTests;

use PHPUnit_Framework_TestCase as TestCase;
use Segment\Analytics;
use \Segment\Consumer\LibCurlConsumer;

class AnalyticsTest extends TestCase
{
  private $analytics;

  function setUp() {
    date_default_timezone_set("UTC");
    $this->analytics = new Analytics(new LibCurlConsumer("oq0vdlg7yi"), array("debug" => true));
  }

  function testTrack() {
    $this->assertTrue($this->analytics->track(array(
      "userId" => "john",
      "event" => "Module PHP Event"
    )));
  }

  function testGroup(){
    $this->assertTrue($this->analytics->group(array(
      "groupId" => "group-id",
      "userId" => "user-id",
      "traits" => array(
        "plan" => "startup"
      )
    )));
  }

  function testMicrotime(){
    $this->assertTrue($this->analytics->page(array(
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
    $this->assertTrue($this->analytics->page(array(
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
    $this->assertTrue($this->analytics->page(array(
      "anonymousId" => "anonymous-id"
    )));
  }

  function testScreen(){
    $this->assertTrue($this->analytics->screen(array(
      "anonymousId" => "anonymous-id",
      "name" => "2048",
      "category" => "game built with php :)",
      "properties" => array(
        "points" => 300
      )
    )));
  }

  function testBasicScreen(){
    $this->assertTrue($this->analytics->screen(array(
      "anonymousId" => "anonymous-id"
    )));
  }

  function testIdentify() {
    $this->assertTrue($this->analytics->identify(array(
      "userId" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testEmptyTraits() {
    $this->assertTrue($this->analytics->identify(array(
      "userId" => "empty-traits"
    )));

    $this->assertTrue($this->analytics->group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits"
    )));
  }

  function testEmptyArrayTraits() {
    $this->assertTrue($this->analytics->identify(array(
      "userId" => "empty-traits",
      "traits" => array()
    )));

    $this->assertTrue($this->analytics->group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits",
      "traits" => array()
    )));
  }

  function testEmptyProperties() {
    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "empty-properties"
    )));

    $this->assertTrue($this->analytics->page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id"
    )));
  }

  function testEmptyArrayProperties(){
    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "empty-properties",
      "properties" => array()
    )));

    $this->assertTrue($this->analytics->page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id",
      "properties" => array()
    )));
  }

  function testAlias() {
    $this->assertTrue($this->analytics->alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }

  function testFlush() {
    $this->assertTrue($this->analytics->flush());
  }

  function testTimestamps() {
    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "integer-timestamp",
      "timestamp" => (int) mktime(0, 0, 0, date('n'), 1, date('Y'))
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "string-integer-timestamp",
      "timestamp" => (string) mktime(0, 0, 0, date('n'), 1, date('Y'))
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "iso8630-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y')))
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "iso8601-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y')))
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "strtotime-timestamp",
      "timestamp" => strtotime('1 week ago')
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "microtime-timestamp",
      "timestamp" => microtime(true)
    )));

    $this->assertTrue($this->analytics->track(array(
      "userId" => "user-id",
      "event" => "invalid-float-timestamp",
      "timestamp" => ((string) mktime(0, 0, 0, date('n'), 1, date('Y'))) . '.'
    )));
  }

  function testFactory()
  {
    $client = Analytics::factory("oq0vdlg7yi");
    $this->assertInstanceOf(Analytics::class, $client);
  }

  function testExceptionForInvalidClassName()
  {
    $this->setExpectedException(\Exception::class);
    $client = Analytics::factory("oq0vdlg7yi", ["consumer" => "ClassDoesNotExist"]);
  }
}

