<?php

declare(strict_types=1);

/**
 * E2E CLI for analytics-php
 *
 * Usage:
 *   php main.php --input '<json>'
 *
 * Input JSON format:
 * {
 *   "writeKey": "...",
 *   "apiHost": "https://...",
 *   "sequences": [
 *     {
 *       "delayMs": 0,
 *       "events": [
 *         {"type": "track", "event": "Test", "userId": "user-1", "properties": {...}},
 *         ...
 *       ]
 *     }
 *   ],
 *   "config": {
 *     "flushAt": 15,
 *     "timeout": 10
 *   }
 * }
 *
 * Output JSON to stdout:
 *   {"success": true, "sentBatches": 1}
 *   {"success": false, "sentBatches": 0, "error": "..."}
 *
 * Exit code: 0 on success, 1 on failure.
 */

// Autoload the Segment SDK from the parent lib/ directory.
spl_autoload_register(function (string $class): void {
    $prefix = 'Segment\\';
    $baseDir = __DIR__ . '/../lib/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * LibCurl subclass that allows overriding the protocol (http:// vs https://).
 * The base class hardcodes $protocol = 'https://', so we extend it to support
 * plain-HTTP targets used by the mock test server.
 */
class E2eLibCurl extends \Segment\Consumer\LibCurl
{
    public function __construct(string $secret, array $options = [])
    {
        parent::__construct($secret, $options);
        if (isset($options['protocol'])) {
            $this->protocol = $options['protocol'];
        }
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function debugLog(string $msg): void
{
    fwrite(STDERR, '[e2e-cli] ' . $msg . PHP_EOL);
}

function outputResult(bool $success, int $sentBatches, string $error = ''): void
{
    $result = [
        'success'     => $success,
        'sentBatches' => $sentBatches,
    ];
    if (!$success && $error !== '') {
        $result['error'] = $error;
    }
    echo json_encode($result) . PHP_EOL;
}

/**
 * Parse the --input argument from $argv.
 *
 * @param array<int,string> $argv
 * @return string|null
 */
function parseInputArg(array $argv): ?string
{
    for ($i = 1, $iMax = count($argv); $i < $iMax; $i++) {
        if ($argv[$i] === '--input' && isset($argv[$i + 1])) {
            return $argv[$i + 1];
        }
        if (strncmp($argv[$i], '--input=', 8) === 0) {
            return substr($argv[$i], 8);
        }
    }
    return null;
}

/**
 * Given a full URL like "https://api.segment.io" or "http://localhost:8080",
 * return just the host[:port] portion that the PHP SDK expects.
 *
 * The PHP SDK prepends "https://" itself (hardcoded in QueueConsumer), so we
 * strip the scheme here and only keep host + optional port.
 *
 * @param string $apiHost
 * @return string
 */
function parseHost(string $apiHost): string
{
    // Remove trailing slash
    $apiHost = rtrim($apiHost, '/');

    // Strip scheme
    $apiHost = preg_replace('#^https?://#', '', $apiHost);

    // Remove any path component — keep only host[:port]
    $parts = explode('/', $apiHost, 2);

    return $parts[0];
}

/**
 * Build the options array for Segment\Client.
 *
 * @param array<string,mixed>   $input
 * @param array<int,string>    &$errors   collected error messages
 * @return array<string,mixed>
 */
function buildClientOptions(array $input, array &$errors): array
{
    $config  = $input['config'] ?? [];
    $apiHost = $input['apiHost'] ?? '';

    // Determine protocol from the apiHost scheme (default https://).
    $scheme = 'https://';
    if (preg_match('#^(https?)://#i', $apiHost, $m)) {
        $scheme = strtolower($m[1]) . '://';
    }

    $options = [
        // Use our subclass so we can inject a plain-http:// protocol for the
        // mock test server (the base LibCurl hardcodes https://).
        'consumer'      => E2eLibCurl::class,
        'protocol'      => $scheme,
        'error_handler' => function (int $code, string $message) use (&$errors): void {
            $msg = "HTTP {$code}: {$message}";
            debugLog('SDK error — ' . $msg);
            $errors[] = $msg;
        },
    ];

    if ($apiHost !== '') {
        $options['host'] = parseHost($apiHost);
        debugLog('Using host: ' . $options['host'] . ' (protocol: ' . $scheme . ')');
    }

    if (isset($config['flushAt']) && is_numeric($config['flushAt'])) {
        $options['flush_at'] = (int)$config['flushAt'];
        debugLog('flush_at: ' . $options['flush_at']);
    }

    if (isset($config['timeout']) && is_numeric($config['timeout'])) {
        $options['curl_timeout'] = (int)$config['timeout'];
        debugLog('curl_timeout: ' . $options['curl_timeout']);
    }

    return $options;
}

/**
 * Map an event array from the input JSON to the array accepted by the SDK.
 * Only passes fields that are set in the input event.
 *
 * @param array<string,mixed> $event
 * @return array<string,mixed>
 */
function buildMessage(array $event): array
{
    $fieldMap = [
        'userId',
        'anonymousId',
        'messageId',
        'timestamp',
        'traits',
        'properties',
        'name',
        'category',
        'groupId',
        'previousId',
        'context',
        'integrations',
        'event',
    ];

    $message = [];
    foreach ($fieldMap as $field) {
        if (array_key_exists($field, $event)) {
            $message[$field] = $event[$field];
        }
    }

    return $message;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$inputJson = parseInputArg($argv);

if ($inputJson === null) {
    outputResult(false, 0, 'Missing required --input argument');
    exit(1);
}

$input = json_decode($inputJson, true);

if (!is_array($input)) {
    outputResult(false, 0, 'Failed to parse --input JSON: ' . json_last_error_msg());
    exit(1);
}

$writeKey  = $input['writeKey']  ?? '';
$sequences = $input['sequences'] ?? [];

if ($writeKey === '') {
    outputResult(false, 0, 'Missing writeKey in input');
    exit(1);
}

$errors = [];

// Build client options (error_handler captures into $errors by reference)
$options = buildClientOptions($input, $errors);

debugLog('Creating Segment\\Client with writeKey=' . substr($writeKey, 0, 4) . '...');

$client = new \Segment\Client($writeKey, $options);

$sentBatches = 0;

foreach ($sequences as $seqIndex => $sequence) {
    $delayMs = (int)($sequence['delayMs'] ?? 0);

    if ($delayMs > 0) {
        debugLog("Sequence {$seqIndex}: sleeping {$delayMs}ms");
        usleep($delayMs * 1000);
    }

    $events = $sequence['events'] ?? [];
    debugLog("Sequence {$seqIndex}: processing " . count($events) . ' event(s)');

    foreach ($events as $eventIndex => $event) {
        $type    = $event['type'] ?? '';
        $message = buildMessage($event);

        debugLog("  [{$seqIndex}/{$eventIndex}] Enqueueing {$type}");

        switch ($type) {
            case 'track':
                $client->track($message);
                break;
            case 'identify':
                $client->identify($message);
                break;
            case 'page':
                $client->page($message);
                break;
            case 'screen':
                $client->screen($message);
                break;
            case 'alias':
                $client->alias($message);
                break;
            case 'group':
                $client->group($message);
                break;
            default:
                $errors[] = "Unknown event type: {$type}";
                debugLog("  Unknown event type: {$type}");
                break;
        }
    }
}

debugLog('Flushing...');
$flushOk = $client->flush();

if ($flushOk) {
    $sentBatches = 1;
    debugLog('Flush succeeded');
} else {
    debugLog('Flush returned false');
    $errors[] = 'Flush failed';
}

$hasErrors = !empty($errors);
$success   = $flushOk && !$hasErrors;

if ($success) {
    outputResult(true, $sentBatches);
    exit(0);
} else {
    $errorMsg = implode('; ', $errors);
    outputResult(false, $sentBatches, $errorMsg);
    exit(1);
}
