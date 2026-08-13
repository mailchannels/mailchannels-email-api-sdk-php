# Webhooks

MailChannels posts batched delivery events to a URL you register. Events
cover both `send()` and `queue()` sends and use the same payload shape.

## Enroll And Manage

```php
$client->webhooks->create('https://example.com/mailchannels/events');
$client->webhooks->list();     // WebhookListResponse — list of enrolled URLs
$client->webhooks->deleteAll(); // removes ALL enrolled webhooks
```

There is no per-URL delete — `deleteAll()` removes every enrolled webhook
for the customer.

If the endpoint is already enrolled, `create()` throws `ConflictException`.
The endpoint URL must be a valid `http` or `https` URL (validated
client-side before the request is sent); anything else throws
`ValidationException` with code `InvalidWebhookEndpoint`.

## Validate

`$client->webhooks->validate()` sends a synthetic test request to **every**
enrolled webhook and reports each one's response. Useful as a deploy check.

```php
$result = $client->webhooks->validate(requestId: 'deploy-smoke-test');

foreach ($result->results ?? [] as $entry) {
    echo $entry->webhook . ': ' . $entry->result . "\n";
}
```

Provide a `requestId` (max 28 chars) so the test event is easy to spot in
logs. If you don't pass one, MailChannels generates it. The test payload
carries `event: "test"` and a hardcoded sender of `test@mailchannels.com`.

## Inspect Batches

`$client->webhooks->batches()` returns up to 500 batch summaries with
status, status code, duration, and event count. Use it to investigate
failed webhook deliveries.

```php
use MailChannels\Enum\WebhookBatchStatus;

$batches = $client->webhooks->batches(
    statuses: [WebhookBatchStatus::Success, WebhookBatchStatus::ClientError, WebhookBatchStatus::ServerError, WebhookBatchStatus::NoResponse],
    createdAfter: '2026-05-20',
    createdBefore: '2026-05-25', // range cannot exceed 31 days
    webhook: 'https://example.com/mailchannels/events',
    limit: 500,                  // 1..500, default 500
    offset: 0,
);
```

If neither `createdAfter` nor `createdBefore` is set,
the default range is the last 3 days.

## Resend A Batch

`resendBatch()` requires the batch ID you wish to resend.

```php
$result = $client->webhooks->resendBatch(
    batchId: 12345,
);

echo $result->webhookStatusCode . ' (' . $result->durationInMs . "ms)\n";
```

A successful call here means the attempt completed — **not** that your
webhook responded with 2xx. Inspect `webhookStatusCode` (HTTP status your
endpoint returned) and `durationInMs` on the response to check what your
endpoint actually did. Note: `$result->webhookStatusCode` is distinct from
`$result->statusCode` (the MailChannels API's own response status).

## Verify Incoming Webhooks (Crucial)

MailChannels signs every webhook request with an Ed25519 signature per
RFC 9421. The `MailChannels\Webhook\Verifier` class provides stateless
helpers for the verification steps. Ed25519 verification itself is
delegated to your preferred crypto library so the SDK doesn't pull in a
crypto dependency.

```php
use MailChannels\Webhook\Verifier;

$rawBody = file_get_contents('php://input');

/** @var array<string, string>|false $headers */
$headers = getallheaders(); // or however your framework exposes headers
if ($rawBody === false || $headers === false) {
    http_response_code(400);
    return;
}

// 1. Verify the SHA-256 content digest matches the raw body.
if (!Verifier::verifyContentDigest($headers, $rawBody)) {
    http_response_code(400);
    return;
}

// 2. Parse the Signature-Input header.
$params = Verifier::parseSignatureInput($headers['Signature-Input'] ?? '');

// 3. Check that the signature is fresh (default tolerance: 300 s).
if (!Verifier::signatureIsFresh($params)) {
    http_response_code(400);
    return;
}

// 4. Fetch the public key for this key ID.
$keyId     = $params->keyId ?? '';
$publicKey = $client->webhooks->publicKey($keyId)->get('key');
if (!is_string($publicKey) || $publicKey === '') {
    http_response_code(400);
    return;
}

/**
 * Parse PEM-encoded Ed25519 public key into raw bytes.
 */
$publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r", " "], '', $publicKey);

if (!str_starts_with($publicKey, "MCowBQYDK2VwAyEA")) {
    http_response_code(400);
    return;
}

$publicKey = substr($publicKey, strlen("MCowBQYDK2VwAyEA"));

$decodedPublicKey = base64_decode($publicKey, true);
if ($decodedPublicKey === false || $decodedPublicKey === '') {
    http_response_code(400);
    return;
}

if (strlen($decodedPublicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
    http_response_code(400);
    return;
}

// 5. Build the RFC 9421 signature base and verify the Ed25519 signature.
$signatureBase = Verifier::buildSignatureBase('POST', $requestUri, $headers, $rawBody, $params);

// Extract the raw signature bytes from the Signature header.
// The header looks like: sig1=:base64-encoded-bytes:
if (!preg_match('/:[^:]+:$/', $headers['Signature'] ?? '', $m)) {
    http_response_code(400);
    return;
}
$signatureBytes   = base64_decode(trim($m[0], ':'), true);
if ($signatureBytes === false || $signatureBytes === '') {
    http_response_code(400);
    return;
}
if (!sodium_crypto_sign_verify_detached($signatureBytes, $signatureBase, $decodedPublicKey)) {
    http_response_code(400);
    return;
}

// Safe to process from here.
$events = json_decode($rawBody, true);
```

`Verifier::verifyContentDigest()` checks all three content-digest
conditions in one call. `Verifier::signatureIsFresh()` uses a default
tolerance of ±300 seconds; pass `toleranceSeconds:` to override.

**`$rawBody` must be the unmodified request bytes.** If your framework
provides a parsed body, fetch the original bytes separately — not the
parsed array.

Cache `$client->webhooks->publicKey($keyId)` — the key only changes on
rotation, and MailChannels may publish multiple active keys at once during
a rollover. Always fetch by the `keyId` from the incoming request rather
than holding a single "current" key.

### Verifier Helper Reference

| Method | What it does |
| --- | --- |
| `Verifier::parseSignatureInput(string)` | Parses the `Signature-Input` header into a `SignatureParameters` object. |
| `Verifier::signatureKeyId(array $headers)` | Extracts the `keyId` from `Signature-Input` — shortcut to avoid full parsing. |
| `Verifier::verifyContentDigest(array $headers, string $body)` | Checks that `Content-Digest` matches SHA-256 of the body. |
| `Verifier::signatureIsFresh(SignatureParameters, toleranceSeconds: 300)` | Checks the `created` timestamp is within the tolerance window. |
| `Verifier::buildSignatureBase(method, uri, headers, body, params)` | Reconstructs the canonical RFC 9421 signature base string. |

## Event Payloads

Each request body is a JSON **array** of event objects with shared fields:

| Field                       | Type        | Notes                                                                                                                                                   |
|-----------------------------|-------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| `event`                     | `string`    | `'processed'` / `'delivered'` / `'open'` / `'click'` / `'hard-bounced'` / `'soft-bounced'` / `'dropped'` / `'complained'` / `'unsubscribed'` / `'test'` |
| `email`                     | `string`    | Sender email address.                                                                                                                                   |
| `customer_handle`           | `string`    | Your MailChannels account (or sub-account) handle.                                                                                                      |
| `timestamp`                 | `number`    | Unix timestamp.                                                                                                                                         |
| `request_id`                | `string`    | Correlates to the `requestId` from `queue()` / `send()`.                                                                                                |
| `smtp_id`                   | `string`    | SMTP message ID.                                                                                                                                        |
| `campaign_id`               | `string?`   | Present when `campaignId` was set on the send.                                                                                                          |
| `recipients`                | `string[]?` | All recipients in the personalization.                                                                                                                  |
| `status`                    | `string?`   | The SMTP status code received for the message. Use for system logic. Present on `hard-bounced`, `soft-bounced` events.                                  |
| `reason`                    | `string?`   | Human-readable explanation of the status code. Do not use for system logic. Present on `hard-bounced`, `soft-bounced` events.                           |
| `url` / `user_agent` / `ip` | `string?`   | Present on `click` / `open` events.                                                                                                                     |


## Responding

Return any 2xx status code quickly. MailChannels treats anything else as a
failure and may retry. Keep your handler thin: enqueue the event and return,
do the actual processing on a worker.
