<?php

if (!function_exists('json_encode')) {
  throw new Exception('Analytics nees the JSON PHP extension.');
}

require(dirname(__FILE__) . '/analytics/client.php');


$client = new Analytics_Client('testsecret', "Analytics_FileConsumer");
$client->track('Some User', "New Test PHP \nEvent");
$client->identify('Some User', array("trait" => "x\nx"));
sleep(1);

?>