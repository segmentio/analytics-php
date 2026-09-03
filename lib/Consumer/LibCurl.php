<?php

declare(strict_types=1);

namespace Segment\Consumer;

class LibCurl extends QueueConsumer
{
    protected string $type = 'LibCurl';

    /**
     * Send a batch of messages to the API with retries on error
     *
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    public function flushBatch(array $messages): bool
    {
        $body    = $this->payload($messages);
        $payload = json_encode($body);
        $secret  = $this->secret;

        if ($this->compress_request) {
            $payload = gzencode($payload);
        }

        $host = $this->host ?: 'api.segment.io';
        $url  = $this->protocol . $host . '/v1/batch';

        $library   = $messages[0]['context']['library'];
        $userAgent = $library['name'] . '/' . $library['version'];

        $backoffMs          = 500;   // base 500ms per spec
        $backoffCapMs       = 60000; // cap 60s
        $retriesRemaining   = $this->retry_count;
        $attempt            = 0;
        $backoffStartTime   = null;
        $rateLimitStartTime = null;

        while (true) {
            $attempt++;

            $headers = [
                'Content-Type: application/json',
                'User-Agent: ' . $userAgent,
            ];

            if ($this->compress_request) {
                $headers[] = 'Content-Encoding: gzip';
            }

            if ($attempt > 1) {
                $headers[] = 'X-Retry-Count: ' . ($attempt - 1);
            }

            [$responseCode, $responseHeaders, $responseContent, $err] =
                $this->executeHttpRequest($url, $secret, $payload, $headers);

            if ($err) {
                $this->handleError(0, $err);

                return false;
            }

            // 2xx and 3xx are success
            if ($responseCode >= 200 && $responseCode < 400) {
                return true;
            }

            $this->handleError($responseCode, $responseContent);

            if (!$this->isRetryable($responseCode)) {
                return false;
            }

            // Any retryable status with valid Retry-After: use rate-limit path (no budget cost)
            $retryAfterS = $this->parseRetryAfter($responseHeaders['retry-after'] ?? null);
            if ($retryAfterS !== null) {
                if ($rateLimitStartTime === null) {
                    $rateLimitStartTime = microtime(true);
                }
                if ((microtime(true) - $rateLimitStartTime) * 1000 >= $this->max_rate_limit_duration_ms) {
                    return false;
                }
                $sleepMs = min($retryAfterS * 1000, $this->rate_limit_retry_after_cap_s * 1000);
                $this->sleepBeforeRetry($sleepMs, true);
                continue; // Do NOT decrement retriesRemaining
            }

            // No Retry-After: counted backoff
            $retriesRemaining--;
            if ($retriesRemaining <= 0) {
                return false;
            }
            if ($backoffStartTime === null) {
                $backoffStartTime = microtime(true);
            }
            if ((microtime(true) - $backoffStartTime) * 1000 >= $this->max_total_backoff_duration_ms) {
                return false;
            }
            $this->sleepBeforeRetry($backoffMs, false);
            $backoffMs = min($backoffMs * 2, $backoffCapMs);
        }
    }

    /**
     * Execute an HTTP POST request via cURL.
     *
     * Returns [statusCode, responseHeaders, responseBody, curlError].
     * responseHeaders keys are lower-cased.
     *
     * @param string $url
     * @param string $secret
     * @param string $payload
     * @param array  $headers
     * @return array{int, array<string,string>, string|false, string}
     */
    /**
     * Wait before the next attempt. Split out from flushBatch so tests can observe
     * the schedule without re-implementing the retry loop.
     *
     * @param int  $milliseconds how long to wait
     * @param bool $rateLimited  true when the server sent Retry-After, false for counted backoff
     */
    protected function sleepBeforeRetry(int $milliseconds, bool $rateLimited): void
    {
        usleep($milliseconds * 1000);
    }

    protected function executeHttpRequest(string $url, string $secret, string $payload, array $headers): array
    {
        $responseHeaders = [];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERPWD,        $secret . ':');
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT,        $this->curl_timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }

            return strlen($header);
        });

        $responseContent = curl_exec($ch);
        $err             = curl_error($ch);
        $responseCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$responseCode, $responseHeaders, $responseContent, $err];
    }
}
