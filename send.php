<?php

/**
 * require client
 */

require(__DIR__ . "/lib/Segment.php");

/**
 * Args
 */

$args = parse($argv);

/**
 * Make sure both are set
 */

if (!isset($args["secret"])) die("--secret must be given");
if (!isset($args["file"])) die("--file must be given");

/**
 * File contents.
 */

$contents = file_get_contents(__DIR__ . "/" . $args["file"]);
$lines = explode("\n", $contents);

/**
 * Initialize the client.
 */

Segment::init($args["secret"], array(
  "batch_size" => 1,
  "on_error" => function($code, $msg){
    print("$code: $msg\n");
    exit(1);
  }
));

/**
 * Payloads
 */

foreach ($lines as $line) {
  if (!trim($line)) continue;
  $payload = json_decode($line, true);
  $type = $payload["type"];
  call_user_func_array(array("Segment", $type), array($payload));
}

/**
 * Sent
 */

print("sent analytics data");
exit(0);

/**
 * Parse arguments
 */

function parse($argv){
  $ret = [];

  for ($i = 0; $i < count($argv); ++$i) {
    $arg = $argv[$i];
    if ('--' != substr($arg, 0, 2)) continue;
    $ret[substr($arg, 2, strlen($arg))] = trim($argv[++$i]);
  }

  return $ret;
}
