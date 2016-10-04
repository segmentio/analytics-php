<?php

namespace SegmentTests;

use PHPUnit_Framework_TestCase as TestCase;
use Segment\Analytics;
use \Segment\Consumer\SocketConsumer;

class ConsumerSocketTest extends TestCase
{
    private $client;

    function setUp()
    {
        date_default_timezone_set("UTC");
        $this->client = new SocketConsumer("oq0vdlg7yi");
    }

    function testTrack()
    {
        $this->assertTrue($this->client->track(array(
            "userId" => "some-user",
            "event" => "Socket PHP Event"
        )));
    }

    function testIdentify()
    {
        $this->assertTrue($this->client->identify(array(
            "userId" => "Calvin",
            "traits" => array(
                "loves_php" => false,
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
                "type" => "consumer socket test"
            )
        )));
    }

    function testPage()
    {
        $this->assertTrue($this->client->page(array(
            "userId" => "user-id",
            "name" => "analytics-php",
            "category" => "socket",
            "properties" => array(
                "url" => "https://a.url/"
            )
        )));
    }

    function testScreen()
    {
        $this->assertTrue($this->client->page(array(
            "anonymousId" => "anonymousId",
            "name" => "grand theft auto",
            "category" => "socket",
            "properties" => array()
        )));
    }

    function testAlias()
    {
        $this->assertTrue($this->client->alias(array(
            "previousId" => "some-socket",
            "userId" => "new-socket"
        )));
    }

    function testShortTimeout()
    {
        $client = new SocketConsumer("oq0vdlg7yi", array("timeout" => 0.01));

        $this->assertTrue($client->track(array(
            "userId" => "some-user",
            "event" => "Socket PHP Event"
        )));

        $this->assertTrue($client->identify(array(
            "userId" => "some-user",
            "traits" => array()
        )));

        $client->__destruct();
    }

    function testProductionProblems()
    {
        $client = new SocketConsumer("x", array(
            "error_handler" => function () {
                throw new \Exception("Was called");
            }
        ));

        // Shouldn't error out without debug on.
        $client->track(array("user_id" => "some-user", "event" => "Production Problems"));
        $client->__destruct();
    }

    function testDebugProblems()
    {

        $options = array(
            "debug" => true,
            "error_handler" => function ($errno, $errmsg) {
                if ($errno != 400) {
                    throw new \Exception("Response is not 400");
                }
            }
        );

        $client = new SocketConsumer("x", $options);

        # Should error out with debug on.
        $client->track(array("user_id" => "some-user", "event" => "Socket PHP Event"));
        $client->__destruct();
    }


    function testLargeMessage()
    {
        $options = array(
            "debug" => true,
        );

        $client = new SocketConsumer("testsecret", $options);

        $big_property = "";

        for ($i = 0; $i < 10000; $i++) {
            $big_property .= "a";
        }

        $this->assertTrue($client->track(array(
            "userId" => "some-user",
            "event" => "Super Large PHP Event",
            "properties" => array("big_property" => $big_property)
        )));

        $client->__destruct();
    }

    /**
     * @expectedException \RuntimeException
     */
    function testConnectionError()
    {
        $client = new SocketConsumer("x", array(
            "host" => "api.segment.ioooooo",
            "error_handler" => function ($errno, $errmsg) {
                throw new \RuntimeException($errmsg, $errno);
            },
        ));

        $client->track(array("user_id" => "some-user", "event" => "Event"));
        $client->__destruct();
    }
}
