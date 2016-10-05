<?php

namespace SegmentTests;

use PHPUnit_Framework_TestCase as TestCase;
use Segment\Analytics;
use \Segment\Consumer\ForkCurlConsumer;

class ConsumerForkCurlTest extends TestCase
{
    private $client;

    function setUp()
    {
        date_default_timezone_set("UTC");
        $this->client = new ForkCurlConsumer("oq0vdlg7yi", array("debug" => true));
    }

    function testTrack()
    {
        $this->assertTrue($this->client->track(array(
            "userId" => "some-user",
            "event" => "PHP Fork Curl'd\" Event"
        )));
    }

    function testIdentify()
    {
        $this->assertTrue($this->client->identify(array(
            "userId" => "user-id",
            "traits" => array(
                "loves_php" => false,
                "type" => "consumer fork-curl test",
                "birthday" => time()
            )
        )));
    }

    function testGroup()
    {
        $this->assertTrue($this->client->group(array(
            "userId" => "user-id",
            "groupId" => "group-id",
            "traits" => array(
                "type" => "consumer fork-curl test"
            )
        )));
    }

    function testPage()
    {
        $this->assertTrue($this->client->page(array(
            "userId" => "userId",
            "name" => "analytics-php",
            "category" => "fork-curl",
            "properties" => array(
                "url" => "https://a.url/"
            )
        )));
    }

    function testScreen()
    {
        $this->assertTrue($this->client->page(array(
            "anonymousId" => "anonymous-id",
            "name" => "grand theft auto",
            "category" => "fork-curl",
            "properties" => array()
        )));
    }


    function testAlias()
    {
        $this->assertTrue($this->client->alias(array(
            "previousId" => "previous-id",
            "userId" => "user-id"
        )));
    }
}

?>
