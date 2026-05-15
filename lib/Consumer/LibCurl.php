<?php

declare(strict_types=1);

namespace Segment\Consumer;

class LibCurl extends QueueConsumer
{
    protected string $type = 'LibCurl';

    /**
     * Send a batch of messages to the API with spec-compliant retry logic:
     * - 2xx/3xx: success
     * - 429 + Retry-After: sleep without consuming retry budget
     * - 429 without Retry-After / other retryable (5xx except 501/505/511,
     *   408/410/460): exponential backoff, counts against retry budget
     * - Non-retryable 4xx / 501/505/511: drop immediately
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

        $backoffMs          = 500;   // base 500ms per e2e spec
        $backoffCapMs       = 60000; // cap 60s
        $retriesRemaining   = $this->retry_count;
        $attempt            = 0;
        $backoffStartTime   = null;
        $rateLimitStartTime = null;

        while (true) {
            $attempt++;
            $responseHeaders = [];

            $ch = curl_init();

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

            if ($err) {
                $this->handleError(0, $err);

                return false;
            }

            // 2xx and 3xx are success
            if ($responseCode >= 200 && $responseCode < 400) {
                return true;
            }

            $this->handleError($responseCode, $responseContent);

            // 429: check for Retry-After header first
            if ($responseCode === 429) {
                $retryAfterS = $this->parseRetryAfter($responseHeaders['retry-after'] ?? null);

                if ($retryAfterS !== null) {
                    if ($rateLimitStartTime === null) {
                        $rateLimitStartTime = microtime(true);
                    }

                    if ((microtime(true) - $rateLimitStartTime) * 1000 >= $this->max_rate_limit_duration_ms) {
                        return false;
                    }

                    $sleepMs = min($retryAfterS * 1000, $this->rate_limit_retry_after_cap_s * 1000);
                    usleep($sleepMs * 1000);
                    continue; // Do NOT decrement retriesRemaining
                }
                // No Retry-After: fall through to counted backoff
            }

            if (!$this->isRetryable($responseCode)) {
                return false;
            }

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

            usleep($backoffMs * 1000);
            $backoffMs = min($backoffMs * 2, $backoffCapMs);
        }
    }
}
