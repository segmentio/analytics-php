<?php

require_once(dirname(__FILE__) . "/../lib/Segment.php");

class AnalyticsTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    date_default_timezone_set("UTC");
    Segment::init("oq0vdlg7yi", array("debug" => true,'check_max_request_size'=>true));
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

  function testGroupAnonymous(){
    $this->assertTrue(Segment::group(array(
        "groupId" => "group-id",
        "anonymousId" => "anonymous-id",
        "traits" => array(
            "plan" => "startup"
        )
    )));
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Segment::group() requires userId or anonymousId
   */
  function testGroupNoUser() {
    Segment::group(array(
        "groupId" => "group-id",
        "traits" => array(
            "plan" => "startup"
        )
    ));
  }

  function testMicrotime(){
    $this->assertTrue(Segment::page(array(
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
    $this->assertTrue(Segment::page(array(
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
    $this->assertTrue(Segment::page(array(
      "anonymousId" => "anonymous-id"
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

  function testBasicScreen(){
    $this->assertTrue(Segment::screen(array(
      "anonymousId" => "anonymous-id"
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

  function testEmptyTraits() {
    $this->assertTrue(Segment::identify(array(
      "userId" => "empty-traits"
    )));

    $this->assertTrue(Segment::group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits"
    )));
  }

  function testEmptyArrayTraits() {
    $this->assertTrue(Segment::identify(array(
      "userId" => "empty-traits",
      "traits" => array()
    )));

    $this->assertTrue(Segment::group(array(
      "userId" => "empty-traits",
      "groupId" => "empty-traits",
      "traits" => array()
    )));
  }

  function testEmptyProperties() {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "empty-properties"
    )));

    $this->assertTrue(Segment::page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id"
    )));
  }

  function testEmptyArrayProperties(){
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "empty-properties",
      "properties" => array()
    )));

    $this->assertTrue(Segment::page(array(
      "category" => "empty-properties",
      "name" => "empty-properties",
      "userId" => "user-id",
      "properties" => array()
    )));
  }

  function testAlias() {
    $this->assertTrue(Segment::alias(array(
      "previousId" => "previous-id",
      "userId" => "user-id"
    )));
  }

  function testContextEmpty() {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "Context Test",
      "context" => array()
    )));
  }

  function testContextCustom() {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "Context Test",
      "context" => array(
        "active" => false
      )
    )));
  }

  function testTimestamps() {
    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "integer-timestamp",
      "timestamp" => (int) mktime(0, 0, 0, date('n'), 1, date('Y'))
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "string-integer-timestamp",
      "timestamp" => (string) mktime(0, 0, 0, date('n'), 1, date('Y'))
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "iso8630-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y')))
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "iso8601-timestamp",
      "timestamp" => date(DATE_ATOM, mktime(0, 0, 0, date('n'), 1, date('Y')))
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "strtotime-timestamp",
      "timestamp" => strtotime('1 week ago')
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "microtime-timestamp",
      "timestamp" => microtime(true)
    )));

    $this->assertTrue(Segment::track(array(
      "userId" => "user-id",
      "event" => "invalid-float-timestamp",
      "timestamp" => ((string) mktime(0, 0, 0, date('n'), 1, date('Y'))) . '.'
    )));
  }

    function testCheckMaxRequestSizeBatch()
    {
        $error_handler_invoked = false;

        Segment::init(
            "oq0vdlg7yi",
            array(
                "debug"                  => false,
                "batch_size"             => 100,
                'check_max_request_size' => true,
                "error_handler"          => function ($code, $msg) use (&$error_handler_invoked) { $error_handler_invoked = true; },
            )
        );

        $event = 'test-max-request-size-batch';

        $parameters = array();

        // Add 300 random 16 character strings to list of parameters
        for ( $i = 0; $i < 300; $i++ ) {
            $parameters[] = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 16)), 0, 16);
        }

        /// Create 150 track calls
        for ( $i = 0; $i < 150; $i++ ) {
            $data = array(
                'userId'     => 'user-id',
                'event'      => $event,
                'properties' => $parameters,
                'timestamp'  => time(),
            );

            Segment::track($data);
        }

        $this->assertFalse($error_handler_invoked);
    }


    function testCheckMaxRequestSizeCall()
    {
        $error_handler_invoked = false;

        Segment::init(
            "oq0vdlg7yi",
            array(
                "debug"                  => false,
                'check_max_request_size' => true,
                "error_handler"          => function ($code, $msg) use (&$error_handler_invoked) { $error_handler_invoked = true; },
            )
        );

        $event = 'test-max-request-size-call';

        $parameters = array();

        // Add 70 random 500 character strings to parameters list
        for ( $i = 0; $i < 70; $i++ ) {
            $parameters[] = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 500)), 0, 500);
        }

        $data = array(
            'userId'     => 'user-id',
            'event'      => $event,
            'properties' => $parameters,
            'timestamp'  => time(),
        );

        Segment::track($data);

        $this->assertTrue($error_handler_invoked, 'The error handler was not invoked when a call over 32kb was added.');
    }
}
?>
