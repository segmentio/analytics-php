<?php

require_once(dirname(__FILE__) . "/../lib/Analytics.php");

use SegmentIO\Analytics;

class AnalyticsTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    Analytics::init("testsecret");
  }

  function testTrack() {
    $tracked = Analytics::track("some_user", "Module PHP Event");
    $this->assertTrue($tracked);
  }

  function testIdentify() {
    $identified = Analytics::identify("some_user", array(
                    "name"      => "Calvin",
                    "loves_php" => false,
                    "birthday"  => time(),
                    ));

    $this->assertTrue($identified);
  }

  function testAlias() {
    $aliased = Analytics::alias("some_user", "new_user");
    $this->assertTrue($aliased);
  }
}
?>