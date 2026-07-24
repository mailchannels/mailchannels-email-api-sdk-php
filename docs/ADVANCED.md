# Advanced Usage

This document covers SDK behavior that is useful in production code but too
detailed for the README quick path.

## Typed Responses

Resource methods that have a stable, documented response shape return a
typed value object instead of the generic `MailChannels\Response`. The
typed classes extend `Response`, so array access, iteration, and HTTP
metadata (`statusCode`, `headers`, `rawBody`, `requestId()`) all keep
working — typed properties are layered on top.

```php
use MailChannels\Response\SendResponse;

$response = $client->emails->send($params);
// Static return type: SendResponse (a subclass of Response).

echo $response->statusCode;             // inherited from Response
echo $response->requestId();            // inherited; checks body's
                                        // request_id first, then headers

// /send returns one SendResult per personalization — that's where
// the message id lives. There is no top-level message_id.
foreach ($response->results ?? [] as $result) {
    echo $result->messageId, ' (', $result->status, ")\n";
}

print_r($response['request_id']);       // array access still works
```

The typed properties are nullable because the API omits a given field
whenever it doesn't apply to that response shape — for example, `/send`
in normal mode populates `results`, while dry-run mode populates
`dryRunData` (a `list<string>` of rendered RFC822 message blobs)
instead. Branch on which one is non-null.

Currently typed across the SDK: `Emails::send()` → `SendResponse`,
`Emails::queue()` → `QueuedSendResponse`, plus typed responses for
`Usage`, `CheckDomain`, `Suppressions`, `Dkim`, `Metrics`, `Webhooks`,
and `SubAccounts`. A handful of 204-style endpoints still return the
generic `Response` because they have no body to type.

### Response validation

When a typed response is constructed, the SDK validates the *type* of
every modeled field that's present in the body. If, say, a
`SendResult.index` arrives as a string instead of an int, the SDK throws
`MailChannels\Exception\ResponseValidationException`. **Missing fields
are not errors** — they're nulled out, so the SDK stays forward-
compatible when the API adds or omits optional fields.

```php
use MailChannels\Exception\ResponseValidationException;

try {
    $response = $client->emails->send($params);
} catch (ResponseValidationException $e) {
    // The HTTP call succeeded but the response shape doesn't match the
    // SDK's model — usually a sign of a server-side API regression.
    $myLogger->error('MailChannels response shape mismatch', $e->toArray());
    throw $e;
}
```

This is distinct from `MailChannels\Exception\ValidationException`,
which fires *pre-flight* when the request payload your code passed in
is malformed.

## Version And User-Agent

The package exports its version so applications can log it at startup or
include it in diagnostics. The SDK uses the same value in its `User-Agent`
header.

```php
use MailChannels\Version;

echo Version::VERSION;        // e.g. "0.1.0"
echo Version::userAgent();    // "mailchannels-php/0.1.0"
```

If you need to override the User-Agent (for example, to append your
application name), pass `userAgent` to the `Client` constructor or to a
`Config` instance.

```php
use MailChannels\Client;
use MailChannels\Version;

$client = new Client(
    apiKey: getenv('MAILCHANNELS_API_KEY') ?: '',
    userAgent: 'my-app/1.2 ' . Version::userAgent(),
);
```

## Client And Config Reuse

A `Client` wraps a PSR-18 transport. The underlying PSR-18 implementation
(Guzzle, Symfony, etc.) keeps connection pools and TLS sessions warm across
requests, so **reuse one `Client` per process** when sending multiple
messages. Creating a new `Client` per request loses those benefits and
may exhaust file descriptors under load.

For applications that hold a long-lived `Client` alongside other components,
build it from an immutable `Config` value object:

```php
use MailChannels\Client;
use MailChannels\Config;

$config = new Config(
    apiKey: getenv('MAILCHANNELS_API_KEY') ?: '',
    baseUrl: 'https://api.mailchannels.net/tx/v1',
);

$client = Client::fromConfig($config);
```

`Config` is `readonly` and exposes `withApiKey()` for the common case of
swapping credentials at runtime without rebuilding everything else (logger,
HTTP client, factories).

## Multi-Tenant Patterns

The SDK supports two natural shapes for SaaS-style multi-tenancy:

1. **One `Client` per tenant**, each pinned to a sub-account API key. This
   is the simplest model and keeps a single PSR-18 client per tenant.
   ```php
   $tenantClient = new Client(
       apiKey: $tenantApiKey,
       httpClient: $sharedGuzzle,  // share the transport
   );
   ```

2. **One parent `Client`** that calls `subAccounts->apiKeys->create($handle)`
   on demand and constructs the per-tenant `Client` lazily. Cache the
   resulting clients by tenant handle in your application layer.

Use option 1 when tenants are long-lived; option 2 when you provision and
retire tenants frequently.

## Custom HTTP Transports

The SDK accepts any [PSR-18](https://www.php-fig.org/psr/psr-18/) client.
Most users get this from Guzzle or Symfony HTTP Client (see the README's
"Install" section). For custom retry behavior, circuit breakers,
test-isolation, or framework-specific stacks, inject your own.

A common pattern is a wrapping client that decorates an existing PSR-18
implementation:

```php
use GuzzleHttp\Client as Guzzle;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class LoggingPsr18Client implements ClientInterface
{
    public function __construct(
        private readonly ClientInterface $delegate,
        private readonly LoggerInterface $logger,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->logger->debug('http', [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
        ]);
        return $this->delegate->sendRequest($request);
    }
}

$client = new \MailChannels\Client(
    apiKey: getenv('MAILCHANNELS_API_KEY') ?: '',
    httpClient: new LoggingPsr18Client(new Guzzle(), $myLogger),
);
```

See [`examples/custom-http-client.php`](../examples/custom-http-client.php)
for a runnable example.

## Multiple PSR-18 Clients And Discovery

When you don't pass `httpClient` explicitly, the SDK uses
[`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html)
to locate a PSR-18 implementation at runtime. Discovery picks **one**
deterministically, but if your application has both Guzzle and Symfony
HTTP Client installed (common in larger codebases) the choice may surprise
you — request routing, timeouts, and middleware all change depending on
which client wins.

The SDK emits a single PSR-3 `warning` in this case so the ambiguity shows
up in your logs:

```
Multiple PSR-18 HTTP clients are installed; MailChannels SDK
auto-discovery will pick one deterministically. Pass `httpClient` to
MailChannels\Client explicitly to remove the ambiguity.
```

To silence the warning, inject the client you want:

```php
$client = new \MailChannels\Client(
    apiKey: $apiKey,
    httpClient: new GuzzleHttp\Client(),
    requestFactory: new GuzzleHttp\Psr7\HttpFactory(),
    streamFactory: new GuzzleHttp\Psr7\HttpFactory(),
);
```

## PSR-3 Logging Injection

Pass any [PSR-3](https://www.php-fig.org/psr/psr-3/) `LoggerInterface` to
capture every send, error, and discovery decision the SDK makes:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('mailchannels');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

$client = new \MailChannels\Client(
    apiKey: $apiKey,
    logger: $logger,
);
```

The SDK logs at `info` for sends, `debug` for per-request metadata,
`warning` for non-fatal surprises (e.g. the PSR-18 ambiguity above), and
`error` for failed requests. Bodies and API keys are never logged.

## Error Handling

All SDK exceptions extend `MailChannels\Exception\MailChannelsException`,
which itself extends `\RuntimeException`. Catch the most specific subclass
that fits your retry policy:

| HTTP | Exception | Typical action |
|---|---|---|
| 400 / unrecognized 4xx | `InvalidRequestException` | Fix the payload; don't retry |
| 401 | `AuthenticationException` | Check API key |
| 403 | `ForbiddenException` | Check sub-account / scope |
| 404 | `NotFoundException` | Resource doesn't exist |
| 409 | `ConflictException` | Refetch state; retry conditionally |
| 413 | `PayloadTooLargeException` | Shrink attachments / split sends |
| 429 | `RateLimitException` | Honor `Retry-After` |
| 502 | `BadGatewayException` | Retry with backoff |
| 5xx (other) | `ServerException` | Retry with backoff |
| (network) | `TransportException` | Retry; request never reached MailChannels |
| (pre-flight) | `ValidationException` | Fix payload; raised before HTTP |
| (config) | `ConfigurationException` | Fix client setup; not retryable |

Every exception carries structured metadata accessible via getters
(`getStatusCode()`, `getErrorCode()`, `getErrorType()`, `getRequestId()`,
`getRetryAfter()`) and `toArray()` for one-line JSON logging.

See [`examples/error-handling.php`](../examples/error-handling.php) for a
worked example.

## Low-Level Webhook Helpers

MailChannels signs webhooks with Ed25519 following RFC 9421. The high-level
end-to-end example lives in
[`examples/webhook-verify.php`](../examples/webhook-verify.php).

When you need finer-grained control, `MailChannels\Webhook\Verifier`
exposes the individual checks as static methods:

- `Verifier::parseSignatureInput($signatureInputHeader)` — returns a
  `SignatureParameters` value object (key id, algorithm, created
  timestamp, covered components).
- `Verifier::verifyContentDigest($headers, $body)` — verifies the
  `Content-Digest` header matches the body.
- `Verifier::signatureIsFresh($params)` — checks the `created` timestamp
  against the default replay window (300s old, 30s future skew).
- `Verifier::buildSignatureBase($method, $targetUri, $headers, $body, $params)`
  — reconstructs the canonical signature base for the final Ed25519
  check.

The final `sodium_crypto_sign_verify_detached(...)` step uses libsodium,
which ships with PHP 8.2+ — no extra Composer dependency required.
