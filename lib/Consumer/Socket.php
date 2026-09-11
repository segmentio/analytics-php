<?php

declare(strict_types=1);

namespace Segment\Consumer;

class Socket extends QueueConsumer
{
    protected string $type = 'Socket';
    private bool $socket_failed = false;

    /**
     * Creates a new socket consumer for dispatching async requests immediately.
     *
     * @param string $secret
     * @param array $options
     *     number "timeout" - the timeout for connecting
     *     function "error_handler" - function called back on errors.
     *     bool "debug" - whether to use debug output, wait for response.
     */
    public function __construct(string $secret, array $options = [])
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }

        if (!isset($options['host'])) {
            $options['host'] = 'api.segment.io';
        }

        if (!isset($options['tls'])) {
            $options['tls'] = '';
        }

        parent::__construct($secret, $options);
    }

    public function flushBatch($batch): bool
    {
        $socket = $this->createSocket();

        if (!$socket) {
            return false;
        }

        $payload = $this->payload($batch);
        $payload = json_encode($payload);

        $body = $this->createBody($this->options['host'], $payload, 1);
        if ($body === false) {
            return false;
        }

        return $this->makeRequest($socket, $body, $payload);
    }

    /**
     * Open a connection to the target host.
     *
     * @return false|resource
     */
    private function createSocket()
    {
        if ($this->socket_failed) {
            return false;
        }

        $protocol = $this->options['tls'] ? 'tls' : 'ssl';
        $host = $this->options['host'];
        $port = 443;
        $timeout = $this->options['timeout'];

        // Open our socket to the API Server.
        $socket = @pfsockopen(
            $protocol . '://' . $host,
            $port,
            $errno,
            $errstr,
            $timeout
        );

        // If we couldn't open the socket, handle the error.
        if ($socket === false) {
            $this->handleError($errno, $errstr);
            $this->socket_failed = true;
        }

        return $socket;
    }

    /**
     * Create the request body.
     *
     * @param string $host
     * @param string $content
     * @return string body
     */
    private function createBody(string $host, string $content, int $attempt = 1)
    {
        $req = "POST /v1/batch HTTP/1.1\r\n";
        $req .= 'Host: ' . $host . "\r\n";
        $req .= "Content-Type: application/json\r\n";
        $req .= 'Authorization: Basic ' . base64_encode($this->secret . ':') . "\r\n";
        $req .= "Accept: application/json\r\n";

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $content_json = json_decode($content, true);
        $library = $content_json['batch'][0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $req .= "User-Agent: $libName/$libVersion\r\n";

        // X-Retry-Count: omit on first attempt, send on retries
        if ($attempt > 1) {
            $req .= 'X-Retry-Count: ' . ($attempt - 1) . "\r\n";
        }

        // Compress content if compress_request is true
        if ($this->compress_request) {
            $content = gzencode($content);

            $req .= "Content-Encoding: gzip\r\n";
        }

        $req .= 'Content-length: ' . strlen($content) . "\r\n";
        $req .= "\r\n";
        $req .= $content;

        // Verify payload size is below 512KB
        if (strlen($req) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            /** @noinspection ForgottenDebugOutputInspection */
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

        return $req;
    }

    /**
     * Socket consumer retry limitations (maintenance mode):
     *
     * - Retry-After header: NOT fully supported (socket only reads first 2048
     *   bytes of response; full header parsing not implemented). Falls back to
     *   exponential backoff on 429.
     * - Status code classification: Full support (retryable vs non-retryable
     *   per e2e spec, via parent isRetryable()).
     * - X-Retry-Count: Supported.
     * - Backoff: Exponential with cap (maximum_backoff_duration).
     *
     * For full Retry-After support, use the default LibCurl consumer.
     *
     * @param resource|false $socket the handle for the socket
     * @param string $req     request body for this attempt
     * @param string $payload  encoded batch, re-used to rebuild the request on retries
     * @return bool
     */
    private function makeRequest($socket, string $req, string $payload): bool
    {
        $bytes_written = 0;
        $bytes_total   = strlen($req);
        $closed        = false;

        // Retries with exponential backoff until success
        $backoff = 100; // Set initial waiting time to 100ms
        $attempt = 1;

        while (true) {
            // Send request to server
            while (!$closed && $bytes_written < $bytes_total) {
                $written = @fwrite($socket, substr($req, $bytes_written));
                if ($written === false) {
                    $this->handleError(13, 'Failed to write to socket.');
                    $closed = true;
                } else {
                    $bytes_written += $written;
                }
            }

            // Get response for request
            $statusCode = 0;

            if (!$closed) {
                $res        = self::parseResponse(fread($socket, 2048));
                $statusCode = (int)$res['status'];
            }
            fclose($socket);

            // 2xx and 3xx are success
            if ($statusCode >= 200 && $statusCode < 400) {
                return true;
            }

            // Non-retryable or backoff budget exhausted
            if (!$this->isRetryable($statusCode) && $statusCode !== 0) {
                if ($this->debug()) {
                    $this->handleError($res['status'], $res['message']);
                }

                return false;
            }

            if ($backoff >= $this->maximum_backoff_duration) {
                break;
            }

            usleep($backoff * 1000);
            $backoff *= 2;
            $attempt++;

            $socket = $this->createSocket();
            if (!$socket) {
                return false;
            }

            // The request buffer is per-attempt: rebuild it so X-Retry-Count is correct.
            $rebuilt = $this->createBody($this->options['host'], $payload, $attempt);
            if ($rebuilt === false) {
                return false;
            }
            $req = $rebuilt;

            $bytes_written = 0;
            $bytes_total   = strlen($req);
            $closed        = false;
        }

        return false;
    }

    /**
     * Parse our response from the server, check header and body.
     * @param string $res
     * @return array
     *     string $status HTTP code, e.g. "200"
     *     string $message JSON response from the api
     */
    private static function parseResponse(string $res): array
    {
        [$first,] = explode("\n", $res, 2);

        // Response comes back as HTTP/1.1 200 OK
        // Final line contains HTTP response.
        [, $status, $message] = explode(' ', $first, 3);

        return [
            'status'  => $status ?? null,
            'message' => $message,
        ];
    }
}
