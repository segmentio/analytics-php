<?php

require_once __DIR__ . "/../lib/Segment.php";

class AnalyticsTest extends PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    date_default_timezone_set("UTC");
    Segment::init("oq0vdlg7yi", array("debug" => true));
  }

  public function testTrack()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "john",
      "event" => "Module PHP Event",
    )));
  }

  public function testGroup()
  {
    $this->assertTrue(Segment::group(array(
      "groupId" => "group-id",
      "userId" => "user-id",
      "traits" => array(
        "plan" => "startup",
      ),
    )));
  }

  public function testGroupAnonymous()
  {
    $this->assertTrue(Segment::group(array(
      "groupId" => "group-id",
      "anonymousId" => "anonymous-id",
      "traits" => array(
        "plan" => "startup",
      ),
    )));
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Segment::group() requires userId or anonymousId
   */
  public function testGroupNoUser()
  {
    Segment::group(array(
      "groupId" => "group-id",
      "traits" => array(
        "plan" => "startup",
      ),
    ));
  }

  public function testMicrotime()
  {
    $this->assertTrue(Segment::page(array(
      "anonymousId" => "anonymous-id",
      "name" => "analytics-php-microtime",
      "category" => "docs",
      "timestamp" => microtime(true),
      "properties" => array(
        "path" => "/docs/libraries/php/",
        "url" => "https://segment.io/docs/libraries/php/",
      ),
    )));
  }

  public function testPage()
  {
    $this->assertTrue(Segment::page(array(
      "anonymousId" => "anonymous-id",
      "name" => "analytics-php",
      "category" => "docs",
      "properties" => array(
        "path" => "/docs/libraries/php/",
        "url" => "https://segment.io/docs/libraries/php/",
      ),
    )));
  }

  public function testBasicPage()
  {
    $this->assertTrue(Segment::page(array(
      "anonymousId" => "anonymous-id",
    )));
  }

  public function testScreen()
  {
    $this->assertTrue(Segment::screen(array(
      "anonymousId" => "anonymous-id",
      "name" => "2048",
      "category" => "game built with php :)",
      "properties" => array(
        "points" => 300
      ),
    )));
  }

  public function testBasicScreen()
  {
    $this->assertTrue(Segment::screen(array(
      "anonymousId" => "anonymous-id"
    )));
  }

  public function testIdentify()
  {
    $this->assertTrue(Segment::identify(array(
      "userId" => "doe",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time(),
      ),
    )));
  }

  public function testEmptyTraits()
  {
    $this->assertTrue(Segment::identify(array(
      "userId" => "empty-traits",
    )));

    $this->assertTrue(Segment::group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits",
    )));
  }

  public function testEmptyArrayTraits()
  {
    $this->assertTrue(Segment::identify(array(
      "userId" => "empty-traits",
      "traits" => array(),
    )));

    $this->assertTrue(Segment::group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits",
      "traits" => array(),
    )));
  }

  public function testEmptyProperties()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "empty-properties",
    )));

    $this->assertTrue(Segment::page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id",
    )));
  }

  public function testEmptyArrayProperties()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "empty-properties",
      "properties" => array(),
    )));

    $this->assertTrue(Segment::page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id",
      "properties" => array(),
    )));
  }

  public function testAlias()
  {
    $this->assertTrue(Segment::alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id",
    )));
  }

  public function testContextEmpty()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "Context Test",
      "context" => array(),
    )));
  }

  public function testContextCustom()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "Context Test",
      "context" => array(
        "active" => false,
      ),
    )));
  }

  public function testTimestamps()
  {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "integer-timestamp",
      "timestamp" => (int) mktime(0, 0, 0, date('n'), 1, date('Y')),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "string-integer-timestamp",
      "timestamp" => (string) mktime(0, 0, 0, date('n'), 1, date('Y')),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "iso8630-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y'))),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "iso8601-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y'))),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "strtotime-timestamp",
      "timestamp" => strtotime('1 week ago'),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "microtime-timestamp",
      "timestamp" => microtime(true),
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "invalid-float-timestamp",
      "timestamp" => ((string) mktime(0, 0, 0, date('n'), 1, date('Y'))) . '.',
    )));
  }
}
