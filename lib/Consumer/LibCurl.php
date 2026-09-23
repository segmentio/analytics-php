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

            [$responseCode, $responseHeaders, $responseContent, $err, $errno] =
                $this->executeHttpRequest($url, $secret, $payload, $headers);

            if ($err) {
                // The real libcurl errno, not 0: error_handler callbacks branch on it
                // to tell a DNS failure from a timeout from a TLS error.
                $this->handleError($errno, $err);

                return false;
            }

            // Only 2xx is success. curl is not configured to follow redirects, so a
            // 3xx means nothing was uploaded; treating it as success would drop the
            // batch silently. TAPI does not emit 3xx — this shows up when the
            // configured host is a proxy or redirector.
            if ($responseCode >= 200 && $responseCode < 300) {
                return true;
            }

            if ($responseCode >= 300 && $responseCode < 400) {
                $this->handleError(
                    $responseCode,
                    'Unexpected redirect; batch not uploaded. Check whether the configured '
                    . 'host points at a proxy or redirector.'
                );
                return false;
            }

            $this->handleError($responseCode, $responseContent);

            if (!$this->isRetryable($responseCode)) {
                return false;
            }

            // Any retryable status with valid Retry-After: use rate-limit path (no budget cost)
            $retryAfterS = $this->parseRetryAfter($responseHeaders['retry-after'] ?? null);
            if ($retryAfterS !== null) {
                if ($rateLimitStartTime === null) {
                    // hrtime is monotonic; microtime would let a clock adjustment
                    // expire or extend this budget.
                    $rateLimitStartTime = hrtime(true);
                }
                $elapsedMs = (hrtime(true) - $rateLimitStartTime) / 1e6;
                if ($elapsedMs >= $this->max_rate_limit_duration_ms) {
                    // Logged unconditionally: this consumer blocks the caller, and a
                    // request that stopped for the whole budget should not have to be
                    // diagnosed from an absence of output. handleError only writes when
                    // debug is on, which it is not by default.
                    error_log(sprintf(
                        '[Analytics][%s] Rate-limit budget of %dms exhausted; dropping batch',
                        $this->type,
                        $this->max_rate_limit_duration_ms
                    ));
                    return false;
                }
                // Clamped to the remaining budget as well as the cap: the elapsed check
                // above runs before the wait, so without this a check passing just inside
                // the budget would sleep a full Retry-After on top and overshoot it.
                $remainingMs = (int)($this->max_rate_limit_duration_ms - $elapsedMs);
                $sleepMs = min(
                    $retryAfterS * 1000,
                    $this->rate_limit_retry_after_cap_s * 1000,
                    $remainingMs
                );
                $this->sleepBeforeRetry($sleepMs, true);
                continue; // Do NOT decrement retriesRemaining
            }

            // No Retry-After: counted backoff
            // Checked before the decrement: decrementing first spent one retry on
            // the exhaustion test itself, so retry_count of N performed N-1 and a
            // retry_count of 1 performed none at all.
            if ($retriesRemaining <= 0) {
                return false;
            }
            $retriesRemaining--;
            if ($backoffStartTime === null) {
                $backoffStartTime = hrtime(true);
            }
            if ((hrtime(true) - $backoffStartTime) / 1e6 >= $this->max_total_backoff_duration_ms) {
                return false;
            }
            $this->sleepBeforeRetry($backoffMs, false);
            $backoffMs = min($backoffMs * 2, $backoffCapMs);
        }
    }

    /**
     * Wait before the next attempt. Separate from flushBatch so tests can observe the
     * retry schedule by overriding this alone.
     *
     * @param int  $milliseconds how long to wait
     * @param bool $rateLimited  true when the server sent Retry-After, false for counted backoff
     */
    protected function sleepBeforeRetry(int $milliseconds, bool $rateLimited): void
    {
        usleep($milliseconds * 1000);
    }

    /**
     * Execute an HTTP POST request via cURL.
     *
     * Returns [statusCode, responseHeaders, responseBody, curlError, curlErrno].
     * responseHeaders keys are lower-cased.
     *
     * @param string $url
     * @param string $secret
     * @param string $payload
     * @param array  $headers
     * @return array{int, array<string,string>, string|false, string, int}
     */
    protected function executeHttpRequest(string $url, string $secret, string $payload, array $headers): array
    {
        $responseHeaders = [];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
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
        $errno           = curl_errno($ch);
        $responseCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$responseCode, $responseHeaders, $responseContent, $err, $errno];
    }
}
