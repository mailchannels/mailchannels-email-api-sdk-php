# Reading Responses

Every endpoint returns a typed response object that extends
`MailChannels\Response`. All response objects provide array access and
typed properties for endpoint-specific fields.

## Base `Response` Properties

All response objects inherit from `MailChannels\Response`:

```php
$response = $client->emails->queue($params);

$response->statusCode;   // int — HTTP status code
$response->headers;      // array<string, string> — all response headers
$response->rawBody;      // string — raw response body
$response->data;         // array<string, mixed> — parsed JSON body

// Array access maps into ->data:
$response['request_id']; // same as $response->data['request_id']

// Request ID — body's request_id first, then falls back to X-Request-ID header:
$response->requestId();  // ?string
```

## Typed Response Classes (Quick Reference)

Every endpoint's response shape is documented in its domain resource file. This table is a fast lookup:

| Class | Endpoint | Key properties | See |
| --- | --- | --- | --- |
| `QueuedSendResponse` | `emails->queue()` | `queuedAt`, `requestId()` | [sending](sending.md) |
| `SendResponse` | `emails->send()` | `results` (`list<SendResult>`), `dryRunData` | [sending](sending.md) |
| `CheckDomainResponse` | `checkDomain->check()` | `checkResults`, `references` | [domain-checks](domain-checks.md) |
| `DkimKeyResponse` | `dkim->create()` | `dkimDnsRecords` | [dkim](dkim.md) |
| `DkimKeyListResponse` | `dkim->list()` | `dkimDnsRecords` | [dkim](dkim.md) |
| `DkimRotateResponse` | `dkim->rotate()` | `newKey`, `rotatedKey` | [dkim](dkim.md) |
| `UsageResponse` | `usage->retrieve()`, `subAccounts->retrieveUsage()` | `totalUsage`, `periodStartDate`, `periodEndDate` | [metrics-and-usage](metrics-and-usage.md) |
| `MetricsVolumeResponse` etc. | `metrics->volume()` etc. | `buckets` (time-series), totals via `data` | [metrics-and-usage](metrics-and-usage.md) |
| `SuppressionListResponse` | `suppressions->list()`, `suppressions->create()` | `data['suppression_entries']` | [suppressions](suppressions.md) |
| `WebhookListResponse` | `webhooks->list()` | `subscriptions` | [webhooks](webhooks.md) |
| `WebhookBatchListResponse` | `webhooks->batches()` | `batches` (`list<WebhookBatch>`) | [webhooks](webhooks.md) |
| `WebhookValidationResultsResponse` | `webhooks->validate()` | `results` | [webhooks](webhooks.md) |
| `WebhookResendResponse` | `webhooks->resendBatch()` | `webhookStatusCode`, `durationInMs`, `batchId`, `eventCount` | [webhooks](webhooks.md) |
| `WebhookPublicKeyResponse` | `webhooks->publicKey()` | `$response->get('key')` or `$response['key']` | [webhooks](webhooks.md) |

## Empty Responses (204 No Content)

Endpoints that return `204 No Content` (suspend / activate / delete /
limit delete / webhook delete) return a `MailChannels\Response` with an
empty `data` array and `statusCode` of `204`. Check `statusCode` to confirm:

```php
$response = $client->subAccounts->delete('clienta');
assert($response->statusCode === 204);
```

## Raw Array Access

Because all responses implement `ArrayAccess` mapped to `->data`, you can
pass a response directly to `json_encode()` or iterate it:

```php
$response = $client->emails->queue($params);
echo json_encode($response->data); // {"request_id": "...", "queued_at": "..."}
```

Prefer the typed properties (`$response->queuedAt`) over raw access
(`$response['queued_at']`) for new code — they provide PHPStan type
coverage and fail fast on missing fields.
