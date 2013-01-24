<?php

#if (!function_exists('curl_init')) {
#  throw new Exception('Analytics needs the CURL PHP extension.');
#}

#if (!function_exists('json_decode')) {
#  throw new Exception('Analytics nees the JSON PHP extension.');
#}

require(dirname(__FILE__) . '/analytics/client.php');

?>