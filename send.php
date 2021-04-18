<?php

/**
 * require client
 */

require_once(__DIR__ . "/lib/Segment.php");

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

if(!file_exists($old)) {
  print("file: $old does not exist");
  exit(0);
}

if (!rename($old, $file)) {
  print("error renaming from $old to $file\n");
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
  "error_handler" => function($code, $msg){
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
    $total++;
    $payload = json_decode($line, true);
    $dt = new DateTime($payload["timestamp"]);
    $ts = floatval($dt->getTimestamp() . "." . $dt->format("u"));
    $payload["timestamp"] = date("c", (int) $ts);
    $type = $payload["type"];
    $currentBatch[] = $payload;
    // flush before batch gets too big
    if (mb_strlen((json_encode(array('batch' => $currentBatch, 'sentAt' => date("c")))), '8bit') >= 512000) {
        $libCurlResponse = Segment::flush();
        if ($libCurlResponse) {
            $successful += count($currentBatch) - 1;
        } else {
            // todo: maybe write batch to analytics-error.log for more controlled errorhandling
        }
        $currentBatch = array();
    }
    $payload["timestamp"] = $ts;
    call_user_func_array(array("Segment", $type), array($payload));
}

$libCurlResponse = Segment::flush();
if ($libCurlResponse) {
    $successful += $total - $successful;
}
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
