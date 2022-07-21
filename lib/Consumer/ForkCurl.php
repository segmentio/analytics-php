<?php

declare(strict_types=1);

namespace Segment\Consumer;

class ForkCurl extends QueueConsumer
{
    protected string $type = 'ForkCurl';

    /**
     * Make an async request to our API. Fork a curl process, immediately send
     * to the API. If debug is enabled, we wait for the response.
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    public function flushBatch(array $messages): bool
    {
        $body = $this->payload($messages);
        $payload = json_encode($body);

        // Escape for shell usage.
        $payload = escapeshellarg($payload);
        $secret = escapeshellarg($this->secret);

        if ($this->host) {
            $host = $this->host;
        } else {
            $host = 'api.segment.io';
        }
        $path = '/v1/batch';
        $url = $this->protocol . $host . $path;

        $cmd = "curl -u $secret: -X POST -H 'Content-Type: application/json'";

        $tmpfname = '';
        if ($this->compress_request) {
            // Compress request to file
            $tmpfname = tempnam('/tmp', 'forkcurl_');
            $cmd2 = 'echo ' . $payload . ' | gzip > ' . $tmpfname;
            exec($cmd2, $output, $exit);

            if ($exit !== 0) {
                $this->handleError($exit, $output);
                return false;
            }

            $cmd .= " -H 'Content-Encoding: gzip'";

            $cmd .= " --data-binary '@" . $tmpfname . "'";
        } else {
            $cmd .= ' -d ' . $payload;
        }

        $cmd .= " '" . $url . "'";

        // Verify payload size is below 512KB
        if (strlen($payload) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $library = $messages[0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $cmd .= " -H 'User-Agent: $libName/$libVersion'";

        if (!$this->debug()) {
            $cmd .= ' > /dev/null 2>&1 &';
        }

        exec($cmd, $output, $exit);

        if ($exit !== 0) {
            $this->handleError($exit, $output);
        }

        if ($tmpfname !== '') {
            unlink($tmpfname);
        }

        return $exit === 0;
    }
}
