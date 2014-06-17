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

$file = $args["file"];
if ($file[0] != '/') $file = __DIR__ . "/" . $file;

/**
 * Rename the file so we don't write the same calls
 * multiple times
 */

$dir = dirname($file);
$old = $file;
$file = $dir . '/analytics-' . rand() . '.log';

if (!rename($old, $file)) {
  print("error renaming from $old to $new\n");
  exit(1);
}

/**
 * File contents.
 */

$contents = file_get_contents($file);
$lines = explode("\n", $contents);

/**
 * Initialize the client.
 */

Segment::init($args["secret"], array(
  "debug" => true,
  "on_error" => function($code, $msg){
    print("$code: $msg\n");
    exit(1);
  }
));

/**
 * Payloads
 */

$total = 0;
$successful = 0;
foreach ($lines as $line) {
  if (!trim($line)) continue;
  $payload = json_decode($line, true);
  $payload["timestamp"] = strtotime($payload["timestamp"]);
  $type = $payload["type"];
  $ret = call_user_func_array(array("Segment", $type), array($payload));
  if ($ret) $successful++;
  $total++;
  if ($total % 100 === 0) Segment::flush();
}

Segment::flush();
unlink($file);

/**
 * Sent
 */

print("sent $successful from $total requests successfully");
exit(0);

/**
 * Parse arguments
 */

function parse($argv){
  $ret = array();

  for ($i = 0; $i < count($argv); ++$i) {
    $arg = $argv[$i];
    if ('--' != substr($arg, 0, 2)) continue;
    $ret[substr($arg, 2, strlen($arg))] = trim($argv[++$i]);
  }

  return $ret;
}
