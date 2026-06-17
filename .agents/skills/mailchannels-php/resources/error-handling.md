# Error Handling

All SDK exceptions inherit from `MailChannels\Exception\MailChannelsException`
(which itself extends `RuntimeException`). API response errors inherit from
`ApiException` (which inherits from `MailChannelsException`). Catch the
specific exception when behavior should differ; only fall back to the bases
for top-level logging.

## Exception Hierarchy

```
MailChannels\Exception\MailChannelsException    (extends RuntimeException)
├── ConfigurationException        missing API key, JSON encode failure, etc.
├── ValidationException           client-side payload validation (reserved headers, bad address, etc.)
├── TransportException            PSR-18 network/transport failure
├── ResponseValidationException   response field has unexpected type (API shape mismatch — not retryable)
└── ApiException                  any non-2xx response from the API
    ├── AuthenticationException     401 — invalid / missing API key
    ├── ForbiddenException          403 — feature not enabled, sub-account suspended
    ├── NotFoundException           404
    ├── InvalidRequestException     400 — bad payload, validation errors
    ├── ConflictException           409 — duplicate webhook, duplicate suppression, etc.
    ├── PayloadTooLargeException    413 — over the 30 MB limit
    ├── RateLimitException          429 — slow down; check getRetryAfter()
    ├── BadGatewayException         502 — error reaching server
    └── ServerException             5xx — generic server-side failure
```

## Catching Patterns

```php
use MailChannels\Exception\{
    PayloadTooLargeException,
    RateLimitException,
    InvalidRequestException,
    AuthenticationException,
    ForbiddenException,
    ConflictException,
    ServerException,
    BadGatewayException,
    TransportException,
    MailChannelsException,
};

try {
    $client->emails->queue($params);
} catch (PayloadTooLargeException $e) {
    // Split the recipient list and retry, or shrink attachments.
    throw $e;
} catch (RateLimitException $e) {
    $sleepFor = $e->getRetryAfter(); // Retry-After header value (string)
    // back off and retry
    throw $e;
} catch (InvalidRequestException $e) {
    // Application bug — fix the payload, don't retry blindly.
    error_log('Invalid request: ' . json_encode($e->toArray()));
    throw $e;
} catch (AuthenticationException $e) {
    // Wrong API key. Don't retry.
    throw $e;
} catch (ForbiddenException $e) {
    // Feature gated or sub-account suspended. Don't retry.
    throw $e;
} catch (ConflictException $e) {
    // Resource already exists (e.g. duplicate webhook).
    throw $e;
} catch (ServerException | BadGatewayException $e) {
    // Transient. Retry with backoff.
    throw $e;
} catch (TransportException $e) {
    // Network failure before the server responded.
    throw $e;
}
```

The order above matters: catch specific subclasses **before** broader ones.

## Useful Methods On `MailChannelsException`

Every exception exposes:

| Method | Meaning |
| --- | --- |
| `getStatusCode(): ?int` | HTTP status as `int`, or `null` for transport/config errors. |
| `getErrorCode(): ?string` | Stable error code string, e.g. `MissingApiKey`, `ReservedHeader`. |
| `getErrorType(): ?string` | Coarse classification, e.g. `validation`, `authorization`. |
| `getRequestId(): ?string` | Server-assigned ID — include in support tickets. Extracted automatically from response headers. |
| `getRetryAfter(): ?string` | Verbatim `Retry-After` header value. |
| `getSuggestedAction(): ?string` | Server-suggested next step, if any. |
| `getResponseHeaders(): array` | Full response headers. |
| `getResponseBody(): mixed` | Parsed API body or raw string. |
| `toArray(): array` | Structured array suitable for logging. |

```php
try {
    $client->emails->queue($params);
} catch (MailChannelsException $e) {
    error_log(json_encode($e->toArray()) ?: '');
}
```

## Client-Side Validation Errors

These raise `ValidationException` before any HTTP call:

- Missing sender / recipient / subject / content.
- Empty `personalizations` or `content` collections.
- Invalid email-address shape (no `@`, starts or ends with `@`).
- Reserved headers in `headers`.
- Non-string keys or values in `headers`.
- Invalid webhook endpoint URL scheme.
- Invalid sub-account handle or company name.

Failing early gives the caller a precise error rather than a vague 400.

## Retrying And Idempotency

| Exception | Safe to retry? |
| --- | --- |
| `ValidationException`, `InvalidRequestException`, `AuthenticationException`, `ForbiddenException`, `ConflictException`, `PayloadTooLargeException` | **No** — these are caller problems; fix the input or credentials instead. |
| `RateLimitException` | Yes, after `getRetryAfter()` seconds. |
| `ServerException`, `BadGatewayException`, `TransportException` | Yes, with exponential backoff. |

The SDK does **not** retry automatically — each call site decides its
policy. The API also does not offer idempotency keys, so retrying
`emails->send()` / `emails->queue()` after a transient failure can produce a
duplicate send. Build idempotency into the caller (e.g. a unique
`campaignId` + recipient-set check) when at-most-once delivery matters.

For Guzzle, compose retry middleware externally via the handler stack rather
than wrapping SDK calls in a retry loop.
