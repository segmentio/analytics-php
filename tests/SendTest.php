<?php

namespace SegmentTests;

use PHPUnit_Framework_TestCase as TestCase;
use Segment\Analytics;
use \Segment\Consumer\FileConsumer;

class SendTest extends TestCase
{
    private $client;
    private $filename = "/tmp/analytics.log";

    function setUp()
    {
        date_default_timezone_set("UTC");
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }

        $this->client = new FileConsumer("oq0vdlg7yi", array("filename" => $this->filename));
    }

    function tearDown()
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    function testSend()
    {
        for ($i = 0; $i < 200; $i++) {
            $this->client->track(array(
                "userId" => "userId",
                "event" => "event"
            ));
        }
        exec("php --define date.timezone=UTC send.php --secret oq0vdlg7yi --file /tmp/analytics.log", $output);
        $this->assertEquals("sent 200 from 200 requests successfully", trim($output[0]));
        $this->assertFalse(file_exists($this->filename));
    }
}