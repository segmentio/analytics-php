<?php

require_once __DIR__ . "/../lib/Segment.php";

class AxiosClient
{
  private $BaseAddress;
  private $Timeout = 3000;
  private $Authorization = "";
  private $RetryCount = 1;

  public function __construct($baseAddress, $timeout, $username)
  {
    $this->BaseAddress = $baseAddress ? $baseAddress : "https://webhook-e2e.segment.com";
    if ($timeout) {
      $this->Timeout = $timeout;
    }
    if ($username) {
      $this->Authorization = base64_encode($username . ":");
    }
  }

  public function setRetryCount($count)
  {
    $this->RetryCount = $count > 0 ? $count : 1;
  }

  public function get($url)
  {
    for ($i = 0; $i < $this->RetryCount; ++$i) {
      // open connection
      $ch = curl_init();

      // Set timeout
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->Timeout);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->Timeout);

      // Set authorization
      if ($this->Authorization) {
        $header = array();
        $header[] = 'Authorization: Basic ' . $this->Authorization;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      }

      // Set url
      curl_setopt($ch, CURLOPT_URL, $this->BaseAddress . "/" . $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $output = curl_exec($ch);
      $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if (200 == (int) $response) {
        return $output;
      }
      usleep(3);
    }

    return null;
  }
}

class AnalyticsE2ETest extends PHPUnit_Framework_TestCase
{
  private static $WRITE_KEY = "OnMMoZ6YVozrgSBeZ9FpkC0ixH0ycYZn";
  private $id = "";
  private $test_e2e;

  public function setUp()
  {
    $this->test_e2e = isset($_SERVER["RUN_E2E_TESTS"]) && ((bool) json_decode($_SERVER["RUN_E2E_TESTS"]));
    if (!$this->test_e2e) {
      return;
    }
    date_default_timezone_set("UTC");

    $this->id = self::messageId();

    // Segment Write Key for https://segment.com/segment-libraries/sources/analytics_php_e2e_test/overview.
    // This source is configured to send events to a Runscope bucket used by this test.
    Segment::init(self::$WRITE_KEY, array("debug" => true));
    Segment::track(array(
      "userId" => "prateek",
      "event" => "Item Purchased",
      "property" => array(
      "id" => $this->id,
      )
    ));
    Segment::flush();

    // Give some time for events to be delivered from the API to destinations.
    sleep(5);     // 5 seconds.
  }

  public function testE2E()
  {
    if (!$this->test_e2e) {
      return;
    }
    // Verify WEBHOOK_AUTH_USERNAME is defined as system variable
    $this->assertTrue(isset($_SERVER["WEBHOOK_AUTH_USERNAME"]));
    $username = $_SERVER["WEBHOOK_AUTH_USERNAME"];

    $client = new AxiosClient("https://webhook-e2e.segment.com", 10 * 1000, $username);
    $client->setRetryCount(3);

    for ($i = 0; $i < 5; ++$i) {
      // Runscope Bucket for https://webhook-e2e.segment.com/buckets/php.
      $messageResponse = $client->Get("buckets/php?limit=20");
      $this->assertTrue(null != $messageResponse);

      $messages = json_decode($messageResponse, true);

      $count = 0;
      foreach ($messages as $m) {
        $msg = json_decode($m, true);
        if (isset($msg['property']['id']) && $msg['property']['id'] == $this->id) {
          return;
        }
      }

      sleep(5);
    }

    $this->assertTrue(false);
  }

  private static function messageId()
  {
    return sprintf("%04x%04x%04x%04x%04x%04x%04x%04x",
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}
