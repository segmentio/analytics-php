<?php

namespace SegmentTests;

use PHPUnit_Framework_TestCase as TestCase;
use Segment\Analytics;
use \Segment\Consumer\FileConsumer;

class ConsumerFileTest extends TestCase
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

    function testTrack()
    {
        $this->assertTrue($this->client->track(array(
            "userId" => "some-user",
            "event" => "File PHP Event - Microtime",
            "timestamp" => microtime(true),
        )));
        $this->checkWritten("track");
    }

    function testIdentify()
    {
        $this->assertTrue($this->client->identify(array(
            "userId" => "Calvin",
            "traits" => array(
                "loves_php" => false,
                "type" => "analytics.log",
                "birthday" => time()
            )
        )));
        $this->checkWritten("identify");
    }

    function testGroup()
    {
        $this->assertTrue($this->client->group(array(
            "userId" => "user-id",
            "groupId" => "group-id",
            "traits" => array(
                "type" => "consumer analytics.log test",
            )
        )));
    }

    function testPage()
    {
        $this->assertTrue($this->client->page(array(
            "userId" => "user-id",
            "name" => "analytics-php",
            "category" => "analytics.log",
            "properties" => array(
                "url" => "https://a.url/"
            )
        )));
    }

    function testScreen()
    {
        $this->assertTrue($this->client->screen(array(
            "userId" => "userId",
            "name" => "grand theft auto",
            "category" => "analytics.log",
            "properties" => array()
        )));
    }

    function testAlias()
    {
        $this->assertTrue($this->client->alias(array(
            "previousId" => "previous-id",
            "userId" => "user-id"
        )));
        $this->checkWritten("alias");
    }

    function testFlush()
    {
        $this->assertTrue($this->client->flush());
    }

    function testConstructWithoutFilename()
    {
        unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "analytics.log");
        new  FileConsumer("oq0vdlg7yi");
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "analytics.log");
    }

    function testProductionProblems()
    {
        # Open to a place where we should not have write access.
        $client = new FileConsumer("oq0vdlg7yi", array("filename" => "/dev/xxxxxxx"));

        $tracked = $client->track(array("userId" => "some-user", "event" => "my event"));
        $this->assertFalse($tracked);
    }

    function checkWritten($type)
    {
        exec("wc -l " . $this->filename, $output);
        $out = trim($output[0]);
        $this->assertEquals($out, "1 " . $this->filename);
        $str = file_get_contents($this->filename);
        $json = json_decode(trim($str));
        $this->assertEquals($type, $json->type);
        unlink($this->filename);
    }
}