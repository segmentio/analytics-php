# analytics-php e2e-cli

A small CLI tool that drives the analytics-php SDK in end-to-end tests.

## Requirements

- PHP 7.4 or 8.x

No `composer install` is required — `main.php` uses a simple `spl_autoload_register`
to load the SDK classes directly from `../lib/`.

## Usage

```bash
php main.php --input '<json>'
```

### Input JSON format

```json
{
  "writeKey": "YOUR_WRITE_KEY",
  "apiHost": "https://api.segment.io",
  "sequences": [
    {
      "delayMs": 0,
      "events": [
        {
          "type": "track",
          "event": "Test Event",
          "userId": "user-1",
          "properties": { "plan": "pro" }
        },
        {
          "type": "identify",
          "userId": "user-1",
          "traits": { "email": "user@example.com" }
        },
        {
          "type": "page",
          "userId": "user-1",
          "name": "Home",
          "category": "Nav"
        },
        {
          "type": "screen",
          "userId": "user-1",
          "name": "Main Screen"
        },
        {
          "type": "alias",
          "userId": "new-id",
          "previousId": "old-id"
        },
        {
          "type": "group",
          "userId": "user-1",
          "groupId": "group-1",
          "traits": { "name": "Acme Corp" }
        }
      ]
    }
  ],
  "config": {
    "flushAt": 15,
    "timeout": 10
  }
}
```

| Field | Description |
|---|---|
| `writeKey` | Segment write key |
| `apiHost` | Full URL of the Segment API host (scheme is stripped; PHP SDK always uses HTTPS) |
| `sequences[].delayMs` | Milliseconds to wait before processing this sequence |
| `sequences[].events` | List of events to send |
| `config.flushAt` | Number of events to accumulate before auto-flushing (maps to `flush_at`) |
| `config.timeout` | cURL timeout in seconds (maps to `curl_timeout`) |

### Supported event types

`track`, `identify`, `page`, `screen`, `alias`, `group`

### Output JSON (stdout)

On success:

```json
{"success": true, "sentBatches": 1}
```

On failure:

```json
{"success": false, "sentBatches": 0, "error": "HTTP 400: Bad Request"}
```

Exit code is `0` on success and `1` on failure. Debug logs are written to **stderr**.

## Running E2E tests

```bash
./run-e2e.sh
```

By default this expects the `sdk-e2e-tests` repo to be checked out alongside
the `analytics-php` repo:

```
parent/
  analytics-php/
    e2e-cli/         ← you are here
  sdk-e2e-tests/
```

Override the location with an environment variable:

```bash
E2E_TESTS_DIR=/path/to/sdk-e2e-tests ./run-e2e.sh
```

Any additional arguments are forwarded to `sdk-e2e-tests/scripts/run-tests.sh`.

## How it works

1. `main.php` parses `--input` JSON from `$argv`.
2. A `Segment\Client` is created with `lib_curl` consumer and the provided options.
3. Each sequence is processed in order; `delayMs` introduces an optional pause.
4. After all events are enqueued, `flush()` is called to send them synchronously.
5. Errors captured by the `error_handler` callback or a `false` return from
   `flush()` cause the script to exit with code `1`.
