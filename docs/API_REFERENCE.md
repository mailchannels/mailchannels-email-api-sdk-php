# MailChannels PHP SDK API Reference

This reference is generated from public class signatures and PHPDoc blocks in `src/` by `scripts/generate-api-reference.php`.

For task-oriented examples, start with the [README](../README.md). This file is the formal public surface reference.

- SDK version: `1.0.1`
- Top-level exports: `89`
- Documented public methods: `202`

## Top-Level Exports

| Name | Kind | Summary |
| --- | --- | --- |
| `MailChannels\Client` | final class | MailChannels API client. Resources are exposed as readonly properties: |
| `MailChannels\Config` | final class |  |
| `MailChannels\Enum\DkimAlgorithm` | enum |  |
| `MailChannels\Enum\DkimKeyStatus` | enum |  |
| `MailChannels\Enum\DkimUpdateStatus` | enum |  |
| `MailChannels\Enum\MetricsInterval` | enum |  |
| `MailChannels\Enum\MetricsSenderType` | enum |  |
| `MailChannels\Enum\MetricsSortOrder` | enum |  |
| `MailChannels\Enum\SuppressionDeleteSource` | enum |  |
| `MailChannels\Enum\SuppressionSource` | enum |  |
| `MailChannels\Enum\SuppressionType` | enum | The type of mail a suppression entry blocks. Each entry in the `suppression_entries` array of POST /suppression-list carries a list of these values. |
| `MailChannels\Enum\WebhookBatchStatus` | enum | Status filter values for the `statuses` query parameter of `GET /webhook-batch`. These classify the HTTP response received from the customer's webhook endpoint (or `NoResponse` if none was received within the timeout). |
| `MailChannels\Exception\ApiException` | exception |  |
| `MailChannels\Exception\AuthenticationException` | exception |  |
| `MailChannels\Exception\BadGatewayException` | exception |  |
| `MailChannels\Exception\ConfigurationException` | exception |  |
| `MailChannels\Exception\ConflictException` | exception |  |
| `MailChannels\Exception\ForbiddenException` | exception |  |
| `MailChannels\Exception\InvalidRequestException` | exception |  |
| `MailChannels\Exception\MailChannelsException` | exception |  |
| `MailChannels\Exception\NotFoundException` | exception |  |
| `MailChannels\Exception\PayloadTooLargeException` | exception |  |
| `MailChannels\Exception\RateLimitException` | exception |  |
| `MailChannels\Exception\ResponseValidationException` | exception | Raised when the MailChannels API returns a 2xx response whose body doesn't match the SDK's expected shape for that endpoint (missing required fields or fields with the wrong type). |
| `MailChannels\Exception\ServerException` | exception |  |
| `MailChannels\Exception\TransportException` | exception |  |
| `MailChannels\Exception\ValidationException` | exception |  |
| `MailChannels\Message\Attachment` | final class |  |
| `MailChannels\Message\Content` | final class |  |
| `MailChannels\Message\EmailAddress` | final class |  |
| `MailChannels\Message\EmailHeaders` | final class | Helper guarding against custom headers that the MailChannels API controls via structured fields. Mirrors the reserved-headers list in the Python SDK. |
| `MailChannels\Message\EmailParams` | final class | Strongly-typed email payload. Use this when you want compile-time-ish validation; for quick scripts you can pass a plain array to Emails::send(). |
| `MailChannels\Message\Personalization` | final class |  |
| `MailChannels\Resource\AbstractResource` | abstract class |  |
| `MailChannels\Resource\CheckDomain` | final class | `POST /check-domain` — checks DKIM, SPF, sender-domain DNS, and Domain Lockdown status for a domain. |
| `MailChannels\Resource\Dkim` | final class |  |
| `MailChannels\Resource\Emails` | final class | `POST /send` and `POST /send-async`. |
| `MailChannels\Resource\Metrics` | final class |  |
| `MailChannels\Resource\SubAccountApiKeys` | final class |  |
| `MailChannels\Resource\SubAccountLimits` | final class |  |
| `MailChannels\Resource\SubAccountSmtpPasswords` | final class |  |
| `MailChannels\Resource\SubAccounts` | final class |  |
| `MailChannels\Resource\Suppressions` | final class |  |
| `MailChannels\Resource\Usage` | final class |  |
| `MailChannels\Resource\Webhooks` | final class | Webhook subscription management. For inbound signature verification, see {@see \MailChannels\Webhook\Verifier} — those helpers are stateless and do not need a Client instance. |
| `MailChannels\Response` | class | Response object returned by SDK calls. Behaves as the decoded JSON body (array-accessible and iterable) while also exposing HTTP metadata. |
| `MailChannels\Response\CheckDomainResponse` | final class | Typed response returned by {@see \MailChannels\Resource\CheckDomain::check()}. |
| `MailChannels\Response\DkimDnsRecord` | final class | One DKIM DNS record suggestion returned by MailChannels — the TXT record you need to publish in your authoritative DNS for the selector to validate. |
| `MailChannels\Response\DkimKeyInfo` | final class | MailChannels-hosted DKIM key metadata. Returned (inside a list or pair) by the DKIM endpoints. |
| `MailChannels\Response\DkimKeyListResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Dkim::list()}. Wraps a list of typed {@see DkimKeyInfo} entries. |
| `MailChannels\Response\DkimKeyResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Dkim::create()} for `POST /domains/{domain}/dkim-keys`. |
| `MailChannels\Response\DkimRotateResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Dkim::rotate()}. Exposes both the freshly-minted selector (`$newKey`) and the just- rotated old selector (`$rotatedKey`), with the new key carrying the `dkimDnsRecords` you need to publish before sending traffic under the new selector. |
| `MailChannels\Response\MetricsBucket` | final class | One time-series bucket entry: an event count for a single time interval. The `metrics/engagement`, `/performance`, `/volume`, and `/recipient-behaviour` endpoints all return lists of these grouped under named bucket categories (`open`, `click`, `delivered`, ...). |
| `MailChannels\Response\MetricsEngagementResponse` | final class | Typed response for `GET /metrics/engagement`. Spec schema: `MetricsEngagement`. |
| `MailChannels\Response\MetricsPerformanceResponse` | final class | Typed response for `GET /metrics/performance`. Spec schema: `MetricsPerformance`. |
| `MailChannels\Response\MetricsRecipientBehaviourResponse` | final class | Typed response for `GET /metrics/recipient-behaviour`. Spec schema: `MetricsRecipientBehaviour`. |
| `MailChannels\Response\MetricsSender` | final class | One entry in the `MetricsSenderResponse::$senders` list — a per-sender (campaign or sub-account, depending on `sender_type`) rollup of message outcomes during the queried time range. |
| `MailChannels\Response\MetricsSenderResponse` | final class | Typed response for `GET /metrics/senders/{sender_type}`. Spec schema: `MetricsSenderResponse`. |
| `MailChannels\Response\MetricsVolumeResponse` | final class | Typed response for `GET /metrics/volume`. Spec schema: `MetricsVolume`. |
| `MailChannels\Response\QueuedSendResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Emails::queue()} for the `/send-async` endpoint. The server queues the message and acks with a request id plus a queued-at timestamp — there is **no** message id at this point because delivery hasn't happened yet. The actual per-message identifiers arrive later via webhook events. |
| `MailChannels\Response\SendResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Emails::send()}. |
| `MailChannels\Response\SendResult` | final class | One per-personalization result entry returned by `/send`. Spec schema: `SendResult`. |
| `MailChannels\Response\SubAccountApiKey` | final class | One sub-account API key record. Spec schema: `APIKey` (renamed to `SubAccountApiKey` to follow PHP conventions and avoid a one-token top-level class name). |
| `MailChannels\Response\SubAccountApiKeyListResponse` | final class | Typed response for `GET /sub-account/{handle}/api-key`. The API returns a bare JSON array of `APIKey` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding. |
| `MailChannels\Response\SubAccountApiKeyResponse` | final class | Typed response for `POST /sub-account/{handle}/api-key`. The API returns a single `APIKey` object directly as the response body. Exposes that as `$apiKey`. |
| `MailChannels\Response\SubAccountDetails` | final class | One sub-account record. Spec schema: `SubAccountDetails`. |
| `MailChannels\Response\SubAccountLimitResponse` | final class | Typed response for both `GET /sub-account/{handle}/limit` (returns `Limit`: `{"sends": int}`) and `PUT /sub-account/{handle}/limit` (returns `LimitUpdateResult`: `{"limit": {"sends": int}}`). |
| `MailChannels\Response\SubAccountListResponse` | final class | Typed response for `GET /sub-account`. The API returns a bare JSON array of `SubAccountDetails` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding, and this class extracts the typed list from there. |
| `MailChannels\Response\SubAccountResponse` | final class | Typed response for `POST /sub-account` (create). The API returns a single `SubAccountDetails` object directly as the response body (no wrapper key). Exposes that as `$subAccount` for typed access. |
| `MailChannels\Response\SubAccountSmtpPassword` | final class | One sub-account SMTP password record. Spec schema: `SMTPPassword` (renamed for consistency with PHP class-name conventions). |
| `MailChannels\Response\SubAccountSmtpPasswordListResponse` | final class | Typed response for `GET /sub-account/{handle}/smtp-password`. The API returns a bare JSON array of `SMTPPassword` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding. |
| `MailChannels\Response\SubAccountSmtpPasswordResponse` | final class | Typed response for `POST /sub-account/{handle}/smtp-password`. The API returns a single `SMTPPassword` object directly as the response body. Exposes that as `$smtpPassword`. |
| `MailChannels\Response\SuppressionEntry` | final class | One suppression list entry returned by `/suppression-list`. |
| `MailChannels\Response\SuppressionListResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Suppressions::list()} and {@see \MailChannels\Resource\Suppressions::create()}. |
| `MailChannels\Response\UsageResponse` | final class | Typed response returned by {@see \MailChannels\Resource\Usage::retrieve()}. |
| `MailChannels\Response\WebhookBatch` | final class | One webhook batch record returned in {@see WebhookBatchListResponse::$webhookBatches}. Spec schema: `WebhookBatch`. |
| `MailChannels\Response\WebhookBatchDuration` | final class | Duration of a webhook batch attempt — how long the receiving endpoint took to respond. Spec schema: `WebhookBatchDuration`. `$unit` is always `"milliseconds"` today but is exposed as a string for forward- compat in case the API ever adds other units. |
| `MailChannels\Response\WebhookBatchListResponse` | final class | Typed response for `GET /webhook-batch`. Spec schema: `WebhookBatchResult`. Wraps a list of {@see WebhookBatch} entries under the `webhook_batches` JSON key. |
| `MailChannels\Response\WebhookEndpointResponse` | final class | The HTTP response MailChannels received from a customer's webhook endpoint during a validation test. Spec schema: `WebhookResponse` (renamed in the SDK to avoid colliding with the SDK's own {@see \MailChannels\Response}). |
| `MailChannels\Response\WebhookListResponse` | final class | Typed response for `GET /webhook`. The API returns a bare JSON array of {@see WebhookSubscription} entries (no envelope); the SDK's {@see \MailChannels\Transport} wraps bare arrays under the `data` key in the decoded body, and this class extracts the typed list from there. |
| `MailChannels\Response\WebhookPublicKeyResponse` | final class | Typed response for `GET /webhook/public-key`. Spec schema: `Key`. |
| `MailChannels\Response\WebhookResendResponse` | final class | Typed response for `POST /webhook-batch/{batch_id}/resend`. Spec schema: `WebhookResendResponse`. |
| `MailChannels\Response\WebhookSubscription` | final class | One enrolled webhook subscription entry returned by `GET /webhook`. Spec schema: `Webhook` (renamed in the SDK to avoid confusion with {@see \MailChannels\Resource\Webhooks}). |
| `MailChannels\Response\WebhookValidationResult` | final class | One per-webhook entry in {@see WebhookValidationResultsResponse::$results}. Spec schema: `WebhookValidationResult`. |
| `MailChannels\Response\WebhookValidationResultsResponse` | final class | Typed response for `POST /webhook/validate`. Spec schema: `WebhookValidationResults`. |
| `MailChannels\Transport` | final class | Internal HTTP transport. Builds PSR-7 requests, sends them through a PSR-18 client, and decodes responses into MailChannels\Response — or raises a typed exception from the MailChannels\Exception namespace. |
| `MailChannels\Version` | final class |  |
| `MailChannels\Webhook\SignatureParameters` | final class | Parsed RFC 9421 Signature-Input header parameters. |
| `MailChannels\Webhook\Verifier` | final class | RFC 9421 helpers for MailChannels webhook payloads. The Verifier parses the Signature-Input header, verifies the Content-Digest, checks signature freshness, and reconstructs the canonical signature base that the signer computed. |

## `MailChannels\Client`

_final class_

MailChannels API client. Resources are exposed as readonly properties:

$client = new MailChannels\Client(apiKey: 'mc-...');
  $client->emails->send([...]);
  $client->subAccounts->create(companyName: 'ACME', handle: 'tenant42');

For full control over base URL, HTTP client, factories, and logging,
build a {@see Config} explicitly and pass it through {@see self::fromConfig()}.

### Public Properties

- `readonly MailChannels\Resource\CheckDomain $checkDomain`
- `readonly MailChannels\Resource\Dkim $dkim`
- `readonly MailChannels\Resource\Emails $emails`
- `readonly MailChannels\Resource\Metrics $metrics`
- `readonly MailChannels\Resource\SubAccounts $subAccounts`
- `readonly MailChannels\Resource\Suppressions $suppressions`
- `readonly MailChannels\Resource\Usage $usage`
- `readonly MailChannels\Resource\Webhooks $webhooks`

### Constructor

```php
public function __construct(
    ?string $apiKey = null,
    ?string $baseUrl = null,
    ?Psr\Http\Client\ClientInterface $httpClient = null,
    ?Psr\Http\Message\RequestFactoryInterface $requestFactory = null,
    ?Psr\Http\Message\StreamFactoryInterface $streamFactory = null,
    ?Psr\Log\LoggerInterface $logger = null,
    string $userAgent = '',
    ?MailChannels\Config $config = null
)
```

**Parameters:**
- `$apiKey` (`?string`)
- `$baseUrl` (`?string`)
- `$httpClient` (`?Psr\Http\Client\ClientInterface`)
- `$requestFactory` (`?Psr\Http\Message\RequestFactoryInterface`)
- `$streamFactory` (`?Psr\Http\Message\StreamFactoryInterface`)
- `$logger` (`?Psr\Log\LoggerInterface`)
- `$userAgent` (`string`)
- `$config` (`?MailChannels\Config`)

### Methods

#### `fromConfig()`

```php
public static function fromConfig(
    MailChannels\Config $config
): self
```

Build a client from a pre-constructed {@see Config}. Use this when the convenience-constructor's named arguments aren't enough — for example when a DI container hands you a configured Config singleton.

**Parameters:**
- `$config` (`MailChannels\Config`)

**Returns:** `self`


## `MailChannels\Config`

_final class_

### Constants

- `DEFAULT_BASE_URL = 'https://api.mailchannels.net/tx/v1'`

### Public Properties

- `readonly ?string $apiKey`
- `readonly string $baseUrl`
- `readonly ?Psr\Http\Client\ClientInterface $httpClient`
- `readonly Psr\Log\LoggerInterface $logger`
- `readonly ?Psr\Http\Message\RequestFactoryInterface $requestFactory`
- `readonly ?Psr\Http\Message\StreamFactoryInterface $streamFactory`
- `readonly string $userAgent`

### Constructor

```php
public function __construct(
    ?string $apiKey = null,
    ?string $baseUrl = null,
    ?Psr\Http\Client\ClientInterface $httpClient = null,
    ?Psr\Http\Message\RequestFactoryInterface $requestFactory = null,
    ?Psr\Http\Message\StreamFactoryInterface $streamFactory = null,
    ?Psr\Log\LoggerInterface $logger = null,
    string $userAgent = ''
)
```

**Parameters:**
- `$apiKey` (`?string`)
- `$baseUrl` (`?string`)
- `$httpClient` (`?Psr\Http\Client\ClientInterface`)
- `$requestFactory` (`?Psr\Http\Message\RequestFactoryInterface`)
- `$streamFactory` (`?Psr\Http\Message\StreamFactoryInterface`)
- `$logger` (`?Psr\Log\LoggerInterface`)
- `$userAgent` (`string`)

### Methods

#### `resolvedUserAgent()`

```php
public function resolvedUserAgent(): string
```

**Returns:** `string`

#### `withApiKey()`

```php
public function withApiKey(
    string $apiKey
): self
```

**Parameters:**
- `$apiKey` (`string`)

**Returns:** `self`


## `MailChannels\Enum\DkimAlgorithm`

_enum_

### Cases

- `Rsa = 'rsa'`


## `MailChannels\Enum\DkimKeyStatus`

_enum_

### Cases

- `Active = 'active'`
- `Retired = 'retired'`
- `Revoked = 'revoked'`
- `Rotated = 'rotated'`


## `MailChannels\Enum\DkimUpdateStatus`

_enum_

### Cases

- `Retired = 'retired'`
- `Revoked = 'revoked'`
- `Rotated = 'rotated'`


## `MailChannels\Enum\MetricsInterval`

_enum_

### Cases

- `Hour = 'hour'`
- `Day = 'day'`
- `Week = 'week'`
- `Month = 'month'`


## `MailChannels\Enum\MetricsSenderType`

_enum_

### Cases

- `Campaigns = 'campaigns'`
- `SubAccounts = 'sub-accounts'`


## `MailChannels\Enum\MetricsSortOrder`

_enum_

### Cases

- `Ascending = 'asc'`
- `Descending = 'desc'`


## `MailChannels\Enum\SuppressionDeleteSource`

_enum_

### Cases

- `Api = 'api'`
- `UnsubscribeLink = 'unsubscribe_link'`
- `ListUnsubscribe = 'list_unsubscribe'`
- `HardBounce = 'hard_bounce'`
- `SpamComplaint = 'spam_complaint'`
- `All = 'all'`


## `MailChannels\Enum\SuppressionSource`

_enum_

### Cases

- `Api = 'api'`
- `UnsubscribeLink = 'unsubscribe_link'`
- `ListUnsubscribe = 'list_unsubscribe'`
- `HardBounce = 'hard_bounce'`
- `SpamComplaint = 'spam_complaint'`


## `MailChannels\Enum\SuppressionType`

_enum_

The type of mail a suppression entry blocks. Each entry in the `suppression_entries` array of POST /suppression-list carries a list of these values.

### Cases

- `Transactional = 'transactional'`
- `NonTransactional = 'non-transactional'`


## `MailChannels\Enum\WebhookBatchStatus`

_enum_

Status filter values for the `statuses` query parameter of `GET /webhook-batch`. These classify the HTTP response received from the customer's webhook endpoint (or `NoResponse` if none was received within the timeout).

NOTE: The Email API uses different strings on the response body of each
batch (`1xx_response`, `2xx_response`, ..., `no_response`). The query
parameter uses the short form below. This enum represents only the query
filter form; the response field is left as a plain string in
`Response::get('status')`.

### Cases

- `Informational = '1xx'`
- `Success = '2xx'`
- `Redirection = '3xx'`
- `ClientError = '4xx'`
- `ServerError = '5xx'`
- `NoResponse = 'no_response'`


## `MailChannels\Exception\ApiException`

_exception_ — extends [`MailChannels\Exception\MailChannelsException`](#mailchannelsexceptionmailchannelsexception)


## `MailChannels\Exception\AuthenticationException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\BadGatewayException`

_exception_ — extends [`MailChannels\Exception\ServerException`](#mailchannelsexceptionserverexception)


## `MailChannels\Exception\ConfigurationException`

_exception_ — extends [`MailChannels\Exception\MailChannelsException`](#mailchannelsexceptionmailchannelsexception)


## `MailChannels\Exception\ConflictException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\ForbiddenException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\InvalidRequestException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\MailChannelsException`

_exception_

### Constructor

```php
public function __construct(
    string $message,
    ?int $statusCode = null,
    ?string $errorCode = null,
    ?string $errorType = null,
    ?string $requestId = null,
    ?string $retryAfter = null,
    ?string $suggestedAction = null,
    array $headers = [],
    mixed $responseBody = null,
    ?Throwable $previous = null
)
```

**Parameters:**
- `$message` (`string`)
- `$statusCode` (`?int`)
- `$errorCode` (`?string`)
- `$errorType` (`?string`)
- `$requestId` (`?string`)
- `$retryAfter` (`?string`)
- `$suggestedAction` (`?string`)
- `$headers` (`array`)
- `$responseBody` (`mixed`)
- `$previous` (`?Throwable`)

### Methods

#### `getErrorCode()`

```php
public function getErrorCode(): ?string
```

**Returns:** `?string`

#### `getErrorType()`

```php
public function getErrorType(): ?string
```

**Returns:** `?string`

#### `getRequestId()`

```php
public function getRequestId(): ?string
```

**Returns:** `?string`

#### `getResponseBody()`

```php
public function getResponseBody(): mixed
```

**Returns:** `mixed`

#### `getResponseHeaders()`

```php
public function getResponseHeaders(): array
```

**Returns:** `array<string,` — string>

#### `getRetryAfter()`

```php
public function getRetryAfter(): ?string
```

**Returns:** `?string`

#### `getStatusCode()`

```php
public function getStatusCode(): ?int
```

**Returns:** `?int`

#### `getSuggestedAction()`

```php
public function getSuggestedAction(): ?string
```

**Returns:** `?string`

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Exception\NotFoundException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\PayloadTooLargeException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\RateLimitException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\ResponseValidationException`

_exception_ — extends [`MailChannels\Exception\MailChannelsException`](#mailchannelsexceptionmailchannelsexception)

Raised when the MailChannels API returns a 2xx response whose body doesn't match the SDK's expected shape for that endpoint (missing required fields or fields with the wrong type).

Distinct from {@see ValidationException}, which is raised pre-flight when
the *request* payload built by the caller is malformed.


## `MailChannels\Exception\ServerException`

_exception_ — extends [`MailChannels\Exception\ApiException`](#mailchannelsexceptionapiexception)


## `MailChannels\Exception\TransportException`

_exception_ — extends [`MailChannels\Exception\MailChannelsException`](#mailchannelsexceptionmailchannelsexception)


## `MailChannels\Exception\ValidationException`

_exception_ — extends [`MailChannels\Exception\MailChannelsException`](#mailchannelsexceptionmailchannelsexception)


## `MailChannels\Message\Attachment`

_final class_

### Constants

- `DISPOSITION_ATTACHMENT = 'attachment'`
- `DISPOSITION_INLINE = 'inline'`

### Public Properties

- `readonly string $content`
- `readonly ?string $contentId`
- `readonly ?string $disposition`
- `readonly string $filename`
- `readonly ?string $type`

### Constructor

```php
public function __construct(
    string $content,
    string $filename,
    ?string $type = null,
    ?string $disposition = null,
    ?string $contentId = null
)
```

**Parameters:**
- `$content` (`string`)
- `$filename` (`string`)
- `$type` (`?string`)
- `$disposition` (`?string`)
- `$contentId` (`?string`)

### Methods

#### `fromBytes()`

```php
public static function fromBytes(
    string $data,
    string $filename,
    ?string $contentType = null,
    string $disposition = 'attachment',
    ?string $contentId = null
): self
```

**Parameters:**
- `$data` (`string`)
- `$filename` (`string`)
- `$contentType` (`?string`)
- `$disposition` (`string`)
- `$contentId` (`?string`)

**Returns:** `self`

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — string>

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — string>


## `MailChannels\Message\Content`

_final class_

### Constants

- `TEMPLATE_MUSTACHE = 'mustache'`
- `TEXT_HTML = 'text/html'`
- `TEXT_PLAIN = 'text/plain'`

### Public Properties

- `readonly ?string $templateType`
- `readonly string $type`
- `readonly string $value`

### Constructor

```php
public function __construct(
    string $type,
    string $value,
    ?string $templateType = null
)
```

**Parameters:**
- `$type` (`string`)
- `$value` (`string`)
- `$templateType` (`?string`)

### Methods

#### `html()`

```php
public static function html(
    string $value
): self
```

**Parameters:**
- `$value` (`string`)

**Returns:** `self`

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — string>

#### `mustacheHtml()`

```php
public static function mustacheHtml(
    string $value
): self
```

**Parameters:**
- `$value` (`string`)

**Returns:** `self`

#### `plain()`

```php
public static function plain(
    string $value
): self
```

**Parameters:**
- `$value` (`string`)

**Returns:** `self`

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — string>


## `MailChannels\Message\EmailAddress`

_final class_

### Public Properties

- `readonly string $email`
- `readonly ?string $name`

### Constructor

```php
public function __construct(
    string $email,
    ?string $name = null
)
```

**Parameters:**
- `$email` (`string`)
- `$name` (`?string`)

### Methods

#### `__toString()`

```php
public function __toString(): string
```

**Returns:** `string`

#### `from()`

```php
public static function from(
    mixed $value
): self
```

Accept any of: a string ('alice@example.com'), an EmailAddress, or an associative array (['email' => '...', 'name' => '...']).

**Parameters:**
- `$value` (`mixed`)

**Returns:** `self`

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — string>

#### `listFrom()`

```php
public static function listFrom(
    iterable $values
): array
```

**Parameters:**
- `$values` (`iterable<mixed>`)

**Returns:** `list<self>`

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — string>


## `MailChannels\Message\EmailHeaders`

_final class_

Helper guarding against custom headers that the MailChannels API controls via structured fields. Mirrors the reserved-headers list in the Python SDK.

### Methods

#### `validate()`

```php
public static function validate(
    array $headers
): array
```

**Parameters:**
- `$headers` (`array`)

**Returns:** `array<string,` — string>


## `MailChannels\Message\EmailParams`

_final class_

Strongly-typed email payload. Use this when you want compile-time-ish validation; for quick scripts you can pass a plain array to Emails::send().

### Public Properties

- `readonly ?array $attachments`
- `readonly ?string $campaignId`
- `readonly array $content`
- `readonly ?string $dkimDomain`
- `readonly ?string $dkimPrivateKey`
- `readonly ?string $dkimSelector`
- `readonly ?MailChannels\Message\EmailAddress $envelopeFrom`
- `readonly MailChannels\Message\EmailAddress $from`
- `readonly ?array $headers`
- `readonly array $personalizations`
- `readonly ?MailChannels\Message\EmailAddress $replyTo`
- `readonly string $subject`
- `readonly ?array $trackingSettings`
- `readonly ?bool $transactional`

### Constructor

```php
public function __construct(
    MailChannels\Message\EmailAddress $from,
    string $subject,
    iterable $personalizations,
    iterable $content,
    ?MailChannels\Message\EmailAddress $replyTo = null,
    ?MailChannels\Message\EmailAddress $envelopeFrom = null,
    ?array $headers = null,
    ?iterable $attachments = null,
    ?bool $transactional = null,
    ?string $campaignId = null,
    ?array $trackingSettings = null,
    ?string $dkimDomain = null,
    ?string $dkimPrivateKey = null,
    ?string $dkimSelector = null
)
```

**Parameters:**
- `$from` (`MailChannels\Message\EmailAddress`)
- `$subject` (`string`)
- `$personalizations` (`iterable<Personalization>`)
- `$content` (`iterable<Content>`)
- `$replyTo` (`?MailChannels\Message\EmailAddress`)
- `$envelopeFrom` (`?MailChannels\Message\EmailAddress`)
- `$headers` (`?array`)
- `$attachments` (`iterable<Attachment>|null`)
- `$transactional` (`?bool`)
- `$campaignId` (`?string`)
- `$trackingSettings` (`?array`)
- `$dkimDomain` (`?string`)
- `$dkimPrivateKey` (`?string`)
- `$dkimSelector` (`?string`)

### Methods

#### `toPayload()`

```php
public function toPayload(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Message\Personalization`

_final class_

### Public Properties

- `readonly ?array $bcc`
- `readonly ?array $cc`
- `readonly ?string $dkimDomain`
- `readonly ?string $dkimPrivateKey`
- `readonly ?string $dkimSelector`
- `readonly ?array $dynamicTemplateData`
- `readonly ?MailChannels\Message\EmailAddress $from`
- `readonly ?array $headers`
- `readonly ?MailChannels\Message\EmailAddress $replyTo`
- `readonly ?string $subject`
- `readonly ?array $substitutions`
- `readonly array $to`

### Constructor

```php
public function __construct(
    iterable $to,
    ?iterable $cc = null,
    ?iterable $bcc = null,
    ?string $subject = null,
    ?MailChannels\Message\EmailAddress $from = null,
    ?MailChannels\Message\EmailAddress $replyTo = null,
    ?array $headers = null,
    ?array $substitutions = null,
    ?array $dynamicTemplateData = null,
    ?string $dkimDomain = null,
    ?string $dkimPrivateKey = null,
    ?string $dkimSelector = null
)
```

**Parameters:**
- `$to` (`iterable<mixed>`)
- `$cc` (`iterable<mixed>|null`)
- `$bcc` (`iterable<mixed>|null`)
- `$subject` (`?string`)
- `$from` (`?MailChannels\Message\EmailAddress`)
- `$replyTo` (`?MailChannels\Message\EmailAddress`)
- `$headers` (`?array`)
- `$substitutions` (`?array`)
- `$dynamicTemplateData` (`?array`)
- `$dkimDomain` (`?string`)
- `$dkimPrivateKey` (`?string`)
- `$dkimSelector` (`?string`)

### Methods

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Resource\AbstractResource`

_abstract class_

### Constructor

```php
public function __construct(
    MailChannels\Client $client
)
```

**Parameters:**
- `$client` (`MailChannels\Client`)


## `MailChannels\Resource\CheckDomain`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

`POST /check-domain` — checks DKIM, SPF, sender-domain DNS, and Domain Lockdown status for a domain.

### Methods

#### `check()`

```php
public function check(
    string $domain,
    ?string $senderId = null,
    ?array $dkimSettings = null
): MailChannels\Response\CheckDomainResponse
```

**Parameters:**
- `$domain` (`string`)
- `$senderId` (`?string`)
- `$dkimSettings` (`?array`)

**Returns:** `MailChannels\Response\CheckDomainResponse`


## `MailChannels\Resource\Dkim`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    string $domain,
    string $selector,
    MailChannels\Enum\DkimAlgorithm|string|null $algorithm = null,
    ?int $keyLength = null
): MailChannels\Response\DkimKeyResponse
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`string`)
- `$algorithm` (`MailChannels\Enum\DkimAlgorithm|string|null`)
- `$keyLength` (`?int`)

**Returns:** `MailChannels\Response\DkimKeyResponse`

#### `list()`

```php
public function list(
    string $domain,
    ?string $selector = null,
    MailChannels\Enum\DkimKeyStatus|string|null $status = null,
    ?int $offset = null,
    ?int $limit = null,
    ?bool $includeDnsRecord = null
): MailChannels\Response\DkimKeyListResponse
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`?string`)
- `$status` (`MailChannels\Enum\DkimKeyStatus|string|null`)
- `$offset` (`?int`)
- `$limit` (`?int`)
- `$includeDnsRecord` (`?bool`)

**Returns:** `MailChannels\Response\DkimKeyListResponse`

#### `rotate()`

```php
public function rotate(
    string $domain,
    string $selector,
    string $newSelector
): MailChannels\Response\DkimRotateResponse
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`string`)
- `$newSelector` (`string`)

**Returns:** `MailChannels\Response\DkimRotateResponse`

#### `updateStatus()`

```php
public function updateStatus(
    string $domain,
    string $selector,
    MailChannels\Enum\DkimUpdateStatus|string $status
): MailChannels\Response
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`string`)
- `$status` (`MailChannels\Enum\DkimUpdateStatus|string`)

**Returns:** `MailChannels\Response`


## `MailChannels\Resource\Emails`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

`POST /send` and `POST /send-async`.

Accepts either a structured EmailParams object or a flexible associative
array. The array form supports both the compact Resend-style shortcuts
(to/cc/bcc/text/html) and the full MailChannels payload shape.

### Methods

#### `normalize()`

```php
public static function normalize(
    MailChannels\Message\EmailParams|array $params
): array
```

**Parameters:**
- `$params` (`MailChannels\Message\EmailParams|array`)

**Returns:** `array<string,` — mixed>

#### `queue()`

```php
public function queue(
    MailChannels\Message\EmailParams|array $params
): MailChannels\Response\QueuedSendResponse
```

Queue an email through `/send-async`. The server queues the message and acks with a `request_id` and `queued_at` timestamp — there is no message ID at this point because delivery hasn't happened yet. This is server-side async — it does NOT make the HTTP call itself async.

**Parameters:**
- `$params` (`MailChannels\Message\EmailParams|array`)

**Returns:** `MailChannels\Response\QueuedSendResponse`

#### `send()`

```php
public function send(
    MailChannels\Message\EmailParams|array $params,
    bool $dryRun = false
): MailChannels\Response\SendResponse
```

**Parameters:**
- `$params` (`MailChannels\Message\EmailParams|array`)
- `$dryRun` (`bool`)

**Returns:** `MailChannels\Response\SendResponse`


## `MailChannels\Resource\Metrics`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `engagement()`

```php
public function engagement(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response\MetricsEngagementResponse
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response\MetricsEngagementResponse`

#### `performance()`

```php
public function performance(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response\MetricsPerformanceResponse
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response\MetricsPerformanceResponse`

#### `recipientBehavior()`

```php
public function recipientBehavior(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response\MetricsRecipientBehaviourResponse
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response\MetricsRecipientBehaviourResponse`

#### `recipientBehaviour()`

```php
public function recipientBehaviour(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response\MetricsRecipientBehaviourResponse
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response\MetricsRecipientBehaviourResponse`

#### `senders()`

```php
public function senders(
    MailChannels\Enum\MetricsSenderType|string $senderType,
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?int $limit = null,
    ?int $offset = null,
    MailChannels\Enum\MetricsSortOrder|string|null $sortOrder = null
): MailChannels\Response\MetricsSenderResponse
```

**Parameters:**
- `$senderType` (`MailChannels\Enum\MetricsSenderType|string`)
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$limit` (`?int`)
- `$offset` (`?int`)
- `$sortOrder` (`MailChannels\Enum\MetricsSortOrder|string|null`)

**Returns:** `MailChannels\Response\MetricsSenderResponse`

#### `volume()`

```php
public function volume(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response\MetricsVolumeResponse
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response\MetricsVolumeResponse`


## `MailChannels\Resource\SubAccountApiKeys`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    string $handle
): MailChannels\Response\SubAccountApiKeyResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\SubAccountApiKeyResponse`

#### `delete()`

```php
public function delete(
    string $handle,
    string|int $keyId
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)
- `$keyId` (`string|int`)

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(
    string $handle
): MailChannels\Response\SubAccountApiKeyListResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\SubAccountApiKeyListResponse`


## `MailChannels\Resource\SubAccountLimits`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `delete()`

```php
public function delete(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

#### `retrieve()`

```php
public function retrieve(
    string $handle
): MailChannels\Response\SubAccountLimitResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\SubAccountLimitResponse`

#### `set()`

```php
public function set(
    string $handle,
    ?int $sends = null,
    ?int $monthlyLimit = null
): MailChannels\Response\SubAccountLimitResponse
```

Set the monthly sending limit for a sub-account. Either `sends` or its documented alias `monthlyLimit` must be provided; the request payload uses `sends`.

**Parameters:**
- `$handle` (`string`)
- `$sends` (`?int`)
- `$monthlyLimit` (`?int`)

**Returns:** `MailChannels\Response\SubAccountLimitResponse`


## `MailChannels\Resource\SubAccountSmtpPasswords`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    string $handle
): MailChannels\Response\SubAccountSmtpPasswordResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\SubAccountSmtpPasswordResponse`

#### `delete()`

```php
public function delete(
    string $handle,
    string|int $passwordId
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)
- `$passwordId` (`string|int`)

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(
    string $handle
): MailChannels\Response\SubAccountSmtpPasswordListResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\SubAccountSmtpPasswordListResponse`


## `MailChannels\Resource\SubAccounts`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Constants

- `COMPANY_NAME_MAX_LENGTH = 128`
- `COMPANY_NAME_MIN_LENGTH = 3`
- `HANDLE_MAX_LENGTH = 128`
- `HANDLE_MIN_LENGTH = 3`
- `HANDLE_PATTERN = '/^[a-z0-9]+$/'`

### Public Properties

- `readonly MailChannels\Resource\SubAccountApiKeys $apiKeys`
- `readonly MailChannels\Resource\SubAccountLimits $limits`
- `readonly MailChannels\Resource\SubAccountSmtpPasswords $smtpPasswords`

### Constructor

```php
public function __construct(
    MailChannels\Client $client
)
```

**Parameters:**
- `$client` (`MailChannels\Client`)

### Methods

#### `activate()`

```php
public function activate(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

#### `create()`

```php
public function create(
    string $companyName,
    ?string $handle = null
): MailChannels\Response\SubAccountResponse
```

Create a sub-account. `companyName` is required by the API. `handle` is optional — if omitted, MailChannels assigns one. When provided, it must match {@see self::HANDLE_PATTERN}.

**Parameters:**
- `$companyName` (`string`)
- `$handle` (`?string`)

**Returns:** `MailChannels\Response\SubAccountResponse`

#### `delete()`

```php
public function delete(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(
    ?int $limit = null,
    ?int $offset = null
): MailChannels\Response\SubAccountListResponse
```

**Parameters:**
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response\SubAccountListResponse`

#### `retrieveUsage()`

```php
public function retrieveUsage(
    string $handle
): MailChannels\Response\UsageResponse
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response\UsageResponse`

#### `suspend()`

```php
public function suspend(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

#### `validateCompanyName()`

```php
public static function validateCompanyName(
    string $companyName
): void
```

Throw if a caller-supplied company name is missing or out of range. The Email API requires a non-empty value 3-128 characters in length.

**Parameters:**
- `$companyName` (`string`)

**Returns:** `void`

#### `validateHandle()`

```php
public static function validateHandle(
    string $handle
): void
```

Throw if a caller-supplied handle does not match the API contract. Used by {@see self::create()} and exposed publicly for callers that want to validate handles upstream.

**Parameters:**
- `$handle` (`string`)

**Returns:** `void`


## `MailChannels\Resource\Suppressions`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    array $entries,
    ?bool $addToSubAccounts = null
): MailChannels\Response\SuppressionListResponse
```

**Parameters:**
- `$entries` (`array`)
- `$addToSubAccounts` (`?bool`)

**Returns:** `MailChannels\Response\SuppressionListResponse`

#### `delete()`

```php
public function delete(
    string $recipient,
    MailChannels\Enum\SuppressionDeleteSource|string|null $source = null
): MailChannels\Response
```

**Parameters:**
- `$recipient` (`string`)
- `$source` (`MailChannels\Enum\SuppressionDeleteSource|string|null`)

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(
    ?string $recipient = null,
    MailChannels\Enum\SuppressionSource|string|null $source = null,
    ?string $createdBefore = null,
    ?string $createdAfter = null,
    ?int $limit = null,
    ?int $offset = null
): MailChannels\Response\SuppressionListResponse
```

**Parameters:**
- `$recipient` (`?string`)
- `$source` (`MailChannels\Enum\SuppressionSource|string|null`)
- `$createdBefore` (`?string`)
- `$createdAfter` (`?string`)
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response\SuppressionListResponse`


## `MailChannels\Resource\Usage`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `retrieve()`

```php
public function retrieve(): MailChannels\Response\UsageResponse
```

**Returns:** `MailChannels\Response\UsageResponse`


## `MailChannels\Resource\Webhooks`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

Webhook subscription management. For inbound signature verification, see {@see \MailChannels\Webhook\Verifier} — those helpers are stateless and do not need a Client instance.

### Methods

#### `batches()`

```php
public function batches(
    ?string $createdAfter = null,
    ?string $createdBefore = null,
    ?array $statuses = null,
    ?string $webhook = null,
    ?int $limit = null,
    ?int $offset = null
): MailChannels\Response\WebhookBatchListResponse
```

**Parameters:**
- `$createdAfter` (`?string`)
- `$createdBefore` (`?string`)
- `$statuses` (`list<WebhookBatchStatus|string>|null`)
- `$webhook` (`?string`)
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response\WebhookBatchListResponse`

#### `create()`

```php
public function create(
    string $endpoint
): MailChannels\Response
```

**Parameters:**
- `$endpoint` (`string`)

**Returns:** `MailChannels\Response`

#### `deleteAll()`

```php
public function deleteAll(): MailChannels\Response
```

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(): MailChannels\Response\WebhookListResponse
```

**Returns:** `MailChannels\Response\WebhookListResponse`

#### `publicKey()`

```php
public function publicKey(
    string $keyId
): MailChannels\Response\WebhookPublicKeyResponse
```

`GET /webhook/public-key?id=...` — does not require an API key.

**Parameters:**
- `$keyId` (`string`)

**Returns:** `MailChannels\Response\WebhookPublicKeyResponse`

#### `resendBatch()`

```php
public function resendBatch(
    int $batchId
): MailChannels\Response\WebhookResendResponse
```

`POST /webhook-batch/{batch_id}/resend` — synchronously re-deliver a failed batch.

**Parameters:**
- `$batchId` (`int`)

**Returns:** `MailChannels\Response\WebhookResendResponse`

#### `validate()`

```php
public function validate(
    ?string $requestId = null
): MailChannels\Response\WebhookValidationResultsResponse
```

**Parameters:**
- `$requestId` (`?string`)

**Returns:** `MailChannels\Response\WebhookValidationResultsResponse`

#### `validateEndpoint()`

```php
public static function validateEndpoint(
    string $endpoint
): void
```

Validate that an endpoint is a well-formed http(s) URL. MailChannels delivers webhook events over HTTP(S) only; other schemes are rejected before the request is sent.

**Parameters:**
- `$endpoint` (`string`)

**Returns:** `void`


## `MailChannels\Response`

_class_

Response object returned by SDK calls. Behaves as the decoded JSON body (array-accessible and iterable) while also exposing HTTP metadata.

Subclassed by per-endpoint typed responses under `MailChannels\Response\`
(e.g. {@see \MailChannels\Response\SendResponse}) — those add typed
properties on top of the array-accessible base.

### Public Properties

- `readonly array $data`
- `readonly array $headers`
- `readonly ?string $rawBody`
- `readonly int $statusCode`

### Constructor

```php
public function __construct(
    array $data,
    int $statusCode,
    array $headers = [],
    ?string $rawBody = null
)
```

**Parameters:**
- `$data` (`array`)
- `$statusCode` (`int`)
- `$headers` (`array`)
- `$rawBody` (`?string`)

### Methods

#### `count()`

```php
public function count(): int
```

**Returns:** `int`

#### `get()`

```php
public function get(
    string $key,
    mixed $default = null
): mixed
```

**Parameters:**
- `$key` (`string`)
- `$default` (`mixed`)

**Returns:** `mixed`

#### `getIterator()`

```php
public function getIterator(): Traversable
```

**Returns:** `Traversable`

#### `header()`

```php
public function header(
    string $name
): ?string
```

**Parameters:**
- `$name` (`string`)

**Returns:** `?string`

#### `offsetExists()`

```php
public function offsetExists(
    mixed $offset
): bool
```

**Parameters:**
- `$offset` (`mixed`)

**Returns:** `bool`

#### `offsetGet()`

```php
public function offsetGet(
    mixed $offset
): mixed
```

**Parameters:**
- `$offset` (`mixed`)

**Returns:** `mixed`

#### `offsetSet()`

```php
public function offsetSet(
    mixed $offset,
    mixed $value
): void
```

**Parameters:**
- `$offset` (`mixed`)
- `$value` (`mixed`)

**Returns:** `void`

#### `offsetUnset()`

```php
public function offsetUnset(
    mixed $offset
): void
```

**Parameters:**
- `$offset` (`mixed`)

**Returns:** `void`

#### `requestId()`

```php
public function requestId(): ?string
```

**Returns:** `?string`

#### `toArray()`

```php
public function toArray(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\CheckDomainResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\CheckDomain::check()}.

The `/check-domain` endpoint returns deeply nested verdict data — DKIM
results (one per selector), SPF status, Domain Lockdown verdict, and
sender-domain DNS (A/MX) checks. The SDK exposes the top-level grouping
as `$checkResults` (an associative array preserving the API's shape)
and `$references` (documentation URLs the API points at). Drill into
the verdicts via array access:

```php
$r = $client->checkDomain->check('example.com');
echo $r->checkResults['spf']['verdict'];                // 'passed' | 'failed'
echo $r->checkResults['domain_lockdown']['verdict'];
foreach ($r->checkResults['dkim'] ?? [] as $dkim) {
    echo $dkim['dkim_selector'], ': ', $dkim['verdict'], "\n";
}
```

Both fields are nullable. The nested shapes are not further typed at
this layer; callers that want stronger typing should construct their
own value objects from the arrays.

### Public Properties

- `readonly ?array $checkResults`
- `readonly ?array $references`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\DkimDnsRecord`

_final class_

One DKIM DNS record suggestion returned by MailChannels — the TXT record you need to publish in your authoritative DNS for the selector to validate.

Plain value object — not a `Response` subclass. Instances live inside
{@see DkimKeyInfo::$dkimDnsRecords}.

### Public Properties

- `readonly ?string $name`
- `readonly ?string $type`
- `readonly ?string $value`

### Constructor

```php
public function __construct(
    ?string $name = null,
    ?string $type = null,
    ?string $value = null
)
```

**Parameters:**
- `$name` (`?string`)
- `$type` (`?string`)
- `$value` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\DkimKeyInfo`

_final class_

MailChannels-hosted DKIM key metadata. Returned (inside a list or pair) by the DKIM endpoints.

Most fields use snake_case in the API; `$gracePeriodExpiresAt` and
`$retiresAt` are camelCase in the v1 response body — that's a known
API inconsistency the SDK abstracts away. The SDK reads both wire
forms (canonical snake_case first, falling back to the v1 camelCase)
and always exposes them via the camelCase PHP properties, so a future
v2 fix is transparent to callers.

Plain value object — not a `Response` subclass. Instances live inside
{@see DkimKeyListResponse::$keys} or
{@see DkimRotateResponse::$newKey}/`$rotatedKey`.

### Public Properties

- `readonly ?string $algorithm`
- `readonly ?string $createdAt`
- `readonly ?array $dkimDnsRecords`
- `readonly ?string $domain`
- `readonly ?string $gracePeriodExpiresAt`
- `readonly ?int $keyLength`
- `readonly ?string $publicKey`
- `readonly ?string $retiresAt`
- `readonly ?string $selector`
- `readonly ?string $status`
- `readonly ?string $statusModifiedAt`

### Constructor

```php
public function __construct(
    ?string $domain = null,
    ?string $selector = null,
    ?string $publicKey = null,
    ?string $status = null,
    ?string $algorithm = null,
    ?int $keyLength = null,
    ?array $dkimDnsRecords = null,
    ?string $createdAt = null,
    ?string $statusModifiedAt = null,
    ?string $gracePeriodExpiresAt = null,
    ?string $retiresAt = null
)
```

**Parameters:**
- `$domain` (`?string`)
- `$selector` (`?string`)
- `$publicKey` (`?string`)
- `$status` (`?string`)
- `$algorithm` (`?string`)
- `$keyLength` (`?int`)
- `$dkimDnsRecords` (`list<DkimDnsRecord>|null`)
- `$createdAt` (`?string`)
- `$statusModifiedAt` (`?string`)
- `$gracePeriodExpiresAt` (`?string`)
- `$retiresAt` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\DkimKeyListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Dkim::list()}. Wraps a list of typed {@see DkimKeyInfo} entries.

```php
$list = $client->dkim->list('example.com', includeDnsRecord: true);
foreach ($list->keys ?? [] as $key) {
    echo $key->selector, ' (', $key->status, ")\n";
    foreach ($key->dkimDnsRecords ?? [] as $record) {
        echo "  publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
    }
}
```

### Public Properties

- `readonly ?array $keys`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\DkimKeyResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Dkim::create()} for `POST /domains/{domain}/dkim-keys`.

The API returns the created `DKIMKeyInfo` object directly as the
response body (no wrapper key in the JSON). This class exposes that
info as `$key` for ergonomic typed access while keeping the body
itself array-accessible via the inherited {@see Response} surface.

```php
$created = $client->dkim->create('example.com', 'mc1', algorithm: DkimAlgorithm::Rsa);
echo $created->key->selector;
foreach ($created->key->dkimDnsRecords ?? [] as $record) {
    echo "publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
}
```

### Public Properties

- `readonly ?MailChannels\Response\DkimKeyInfo $key`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. An empty body leaves `$key` null.


## `MailChannels\Response\DkimRotateResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Dkim::rotate()}. Exposes both the freshly-minted selector (`$newKey`) and the just- rotated old selector (`$rotatedKey`), with the new key carrying the `dkimDnsRecords` you need to publish before sending traffic under the new selector.

### Public Properties

- `readonly ?MailChannels\Response\DkimKeyInfo $newKey`
- `readonly ?MailChannels\Response\DkimKeyInfo $rotatedKey`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\MetricsBucket`

_final class_

One time-series bucket entry: an event count for a single time interval. The `metrics/engagement`, `/performance`, `/volume`, and `/recipient-behaviour` endpoints all return lists of these grouped under named bucket categories (`open`, `click`, `delivered`, ...).

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?int $count`
- `readonly ?string $periodStart`

### Constructor

```php
public function __construct(
    ?int $count = null,
    ?string $periodStart = null
)
```

**Parameters:**
- `$count` (`?int`)
- `$periodStart` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>

#### `listFromParent()`

```php
public static function listFromParent(
    ?array $parent,
    string $key
): ?array
```

Extract a list of MetricsBucket from `$parent[$key]`. Returns null when the parent itself is null or the key is absent.

**Parameters:**
- `$parent` (`?array`)
- `$key` (`string`)

**Returns:** `list<self>|null`


## `MailChannels\Response\MetricsEngagementResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /metrics/engagement`. Spec schema: `MetricsEngagement`.

Exposes top-level open/click counts plus their time-series breakdowns
under the `*Buckets` properties.

### Public Properties

- `readonly ?int $click`
- `readonly ?array $clickBuckets`
- `readonly ?int $clickTrackingDelivered`
- `readonly ?array $clickTrackingDeliveredBuckets`
- `readonly ?string $endTime`
- `readonly ?int $open`
- `readonly ?array $openBuckets`
- `readonly ?int $openTrackingDelivered`
- `readonly ?array $openTrackingDeliveredBuckets`
- `readonly ?string $startTime`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\MetricsPerformanceResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /metrics/performance`. Spec schema: `MetricsPerformance`.

Exposes top-level processed/delivered/bounced counts plus their
time-series breakdowns under the `*Buckets` properties.

### Public Properties

- `readonly ?int $bounced`
- `readonly ?array $bouncedBuckets`
- `readonly ?int $delivered`
- `readonly ?array $deliveredBuckets`
- `readonly ?string $endTime`
- `readonly ?int $processed`
- `readonly ?array $processedBuckets`
- `readonly ?string $startTime`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\MetricsRecipientBehaviourResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /metrics/recipient-behaviour`. Spec schema: `MetricsRecipientBehaviour`.

Exposes top-level unsubscribe counts plus their time-series
breakdowns under the `*Buckets` properties.

### Public Properties

- `readonly ?string $endTime`
- `readonly ?string $startTime`
- `readonly ?int $unsubscribeDelivered`
- `readonly ?array $unsubscribeDeliveredBuckets`
- `readonly ?int $unsubscribed`
- `readonly ?array $unsubscribedBuckets`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\MetricsSender`

_final class_

One entry in the `MetricsSenderResponse::$senders` list — a per-sender (campaign or sub-account, depending on `sender_type`) rollup of message outcomes during the queried time range.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?int $bounced`
- `readonly ?int $delivered`
- `readonly ?int $dropped`
- `readonly ?string $name`
- `readonly ?int $processed`

### Constructor

```php
public function __construct(
    ?string $name = null,
    ?int $processed = null,
    ?int $delivered = null,
    ?int $bounced = null,
    ?int $dropped = null
)
```

**Parameters:**
- `$name` (`?string`)
- `$processed` (`?int`)
- `$delivered` (`?int`)
- `$bounced` (`?int`)
- `$dropped` (`?int`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\MetricsSenderResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /metrics/senders/{sender_type}`. Spec schema: `MetricsSenderResponse`.

Differs from the time-series metrics endpoints: this one is a
paginated list of per-sender rollups (`$senders`), not a bucketed
time series. `$limit`/`$offset`/`$total` describe the pagination
window.

### Public Properties

- `readonly ?string $endTime`
- `readonly ?int $limit`
- `readonly ?int $offset`
- `readonly ?array $senders`
- `readonly ?string $startTime`
- `readonly ?int $total`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\MetricsVolumeResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /metrics/volume`. Spec schema: `MetricsVolume`.

Exposes top-level processed/delivered/dropped counts plus their
time-series breakdowns under the `*Buckets` properties.

### Public Properties

- `readonly ?int $delivered`
- `readonly ?array $deliveredBuckets`
- `readonly ?int $dropped`
- `readonly ?array $droppedBuckets`
- `readonly ?string $endTime`
- `readonly ?int $processed`
- `readonly ?array $processedBuckets`
- `readonly ?string $startTime`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\QueuedSendResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Emails::queue()} for the `/send-async` endpoint. The server queues the message and acks with a request id plus a queued-at timestamp — there is **no** message id at this point because delivery hasn't happened yet. The actual per-message identifiers arrive later via webhook events.

Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
helpers. `requestId()` is overridden to check the body's `request_id`
field first, falling back to header inspection.

### Public Properties

- `readonly ?string $queuedAt`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

Build a QueuedSendResponse from a generic {@see Response}, validating field types as it goes.

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

### Methods

#### `requestId()`

```php
public function requestId(): ?string
```

**Returns:** `?string`


## `MailChannels\Response\SendResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Emails::send()}.

Covers two distinct success shapes from `/send`:

- **Normal send (HTTP 202).** Body is a `SendResults` envelope:
  `{ request_id, results: [SendResult] }`. The per-personalization
  message ids live inside `$results[i]->messageId` — `/send` does
  not return a top-level `message_id`.
- **Dry run (HTTP 200).** Body is a `Message`: `{ data: [string, ...] }`
  where each entry is a rendered RFC822 message blob, one per
  personalization. Exposed as `$dryRunData`.

All typed fields are nullable because the API omits a given field
whenever it doesn't apply to that response shape — branch on which
one is non-null.

Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
helpers — `$response['anything']`, `$response->statusCode`,
`$response->headers`, and `$response->requestId()` all keep working.
`requestId()` is overridden here to check the response body's
`request_id` field before falling back to header inspection.

### Public Properties

- `readonly ?array $dryRunData`
- `readonly ?array $results`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

Build a SendResponse from a generic {@see Response}, validating field types as it goes.

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are not errors — they're nulled out so callers can branch on `dryRunData !== null` etc.

### Methods

#### `requestId()`

```php
public function requestId(): ?string
```

**Returns:** `?string`


## `MailChannels\Response\SendResult`

_final class_

One per-personalization result entry returned by `/send`. Spec schema: `SendResult`.

Plain value object — not a `Response` subclass. Instances live inside
{@see SendResponse::$results}, one per personalization in the original
request. `$messageId` is the canonical place to find a message's id —
the `/send` endpoint does not return a top-level `message_id`.

`$status` carries the spec enum values `'sent'` or `'failed'`. The spec
notes that `'sent'` is a temporary status; the terminal status arrives
via webhook events.

### Public Properties

- `readonly ?int $index`
- `readonly ?string $messageId`
- `readonly ?string $reason`
- `readonly ?string $status`

### Constructor

```php
public function __construct(
    ?int $index = null,
    ?string $messageId = null,
    ?string $reason = null,
    ?string $status = null
)
```

**Parameters:**
- `$index` (`?int`)
- `$messageId` (`?string`)
- `$reason` (`?string`)
- `$status` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\SubAccountApiKey`

_final class_

One sub-account API key record. Spec schema: `APIKey` (renamed to `SubAccountApiKey` to follow PHP conventions and avoid a one-token top-level class name).

`$key` is the raw API key string and is only returned by the create
endpoint — the list endpoint omits the secret material. Treat it as
a credential.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?int $id`
- `readonly ?string $key`

### Constructor

```php
public function __construct(
    ?int $id = null,
    ?string $key = null
)
```

**Parameters:**
- `$id` (`?int`)
- `$key` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\SubAccountApiKeyListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /sub-account/{handle}/api-key`. The API returns a bare JSON array of `APIKey` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding.

The list endpoint typically omits the secret `$key` material on
each entry; only `$id` is reliably populated.

### Public Properties

- `readonly ?array $apiKeys`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountApiKeyResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `POST /sub-account/{handle}/api-key`. The API returns a single `APIKey` object directly as the response body. Exposes that as `$apiKey`.

`$apiKey->key` contains the raw key string returned by the create
endpoint — treat it as a credential. The list endpoint omits the
secret material.

### Public Properties

- `readonly ?MailChannels\Response\SubAccountApiKey $apiKey`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountDetails`

_final class_

One sub-account record. Spec schema: `SubAccountDetails`.

Plain value object — not a `Response` subclass. Returned inside
{@see SubAccountListResponse::$subAccounts} or as
{@see SubAccountResponse::$subAccount}.

### Public Properties

- `readonly ?string $companyName`
- `readonly ?bool $enabled`
- `readonly ?string $handle`

### Constructor

```php
public function __construct(
    ?string $handle = null,
    ?string $companyName = null,
    ?bool $enabled = null
)
```

**Parameters:**
- `$handle` (`?string`)
- `$companyName` (`?string`)
- `$enabled` (`?bool`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\SubAccountLimitResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for both `GET /sub-account/{handle}/limit` (returns `Limit`: `{"sends": int}`) and `PUT /sub-account/{handle}/limit` (returns `LimitUpdateResult`: `{"limit": {"sends": int}}`).

The two endpoints have semantically equivalent payloads but
structurally different shapes — GET returns the limit fields at the
top level, PUT wraps them under a `limit` key. The constructor
unwraps the PUT shape transparently so callers see the same typed
`$sends` property in both cases.

### Public Properties

- `readonly ?int $sends`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /sub-account`. The API returns a bare JSON array of `SubAccountDetails` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding, and this class extracts the typed list from there.

### Public Properties

- `readonly ?array $subAccounts`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `POST /sub-account` (create). The API returns a single `SubAccountDetails` object directly as the response body (no wrapper key). Exposes that as `$subAccount` for typed access.

### Public Properties

- `readonly ?MailChannels\Response\SubAccountDetails $subAccount`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountSmtpPassword`

_final class_

One sub-account SMTP password record. Spec schema: `SMTPPassword` (renamed for consistency with PHP class-name conventions).

`$smtpPassword` carries the raw password and is only returned by the
create endpoint — the list endpoint omits the secret. Treat it as a
credential.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?bool $enabled`
- `readonly ?int $id`
- `readonly ?string $smtpPassword`

### Constructor

```php
public function __construct(
    ?int $id = null,
    ?string $smtpPassword = null,
    ?bool $enabled = null
)
```

**Parameters:**
- `$id` (`?int`)
- `$smtpPassword` (`?string`)
- `$enabled` (`?bool`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\SubAccountSmtpPasswordListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /sub-account/{handle}/smtp-password`. The API returns a bare JSON array of `SMTPPassword` entries; {@see \MailChannels\Transport} wraps bare arrays under the `data` key during decoding.

The list endpoint typically omits the raw `$smtpPassword` material
on each entry; only `$id` and `$enabled` are reliably populated.

### Public Properties

- `readonly ?array $smtpPasswords`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SubAccountSmtpPasswordResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `POST /sub-account/{handle}/smtp-password`. The API returns a single `SMTPPassword` object directly as the response body. Exposes that as `$smtpPassword`.

`$smtpPassword->smtpPassword` is the raw credential returned by the
create endpoint — treat as a secret.

### Public Properties

- `readonly ?MailChannels\Response\SubAccountSmtpPassword $smtpPassword`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\SuppressionEntry`

_final class_

One suppression list entry returned by `/suppression-list`.

Plain value object — not a `Response` subclass. Instances live inside
{@see SuppressionListResponse::$suppressionList} and are constructed by
that class's constructor.

### Public Properties

- `readonly ?string $createdAt`
- `readonly ?string $notes`
- `readonly ?string $recipient`
- `readonly ?string $sender`
- `readonly ?string $source`
- `readonly ?array $suppressionTypes`

### Constructor

```php
public function __construct(
    ?string $recipient = null,
    ?array $suppressionTypes = null,
    ?string $notes = null,
    ?string $source = null,
    ?string $sender = null,
    ?string $createdAt = null
)
```

**Parameters:**
- `$recipient` (`?string`)
- `$suppressionTypes` (`list<string>|null`)
- `$notes` (`?string`)
- `$source` (`?string`)
- `$sender` (`?string`)
- `$createdAt` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\SuppressionListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Suppressions::list()} and {@see \MailChannels\Resource\Suppressions::create()}.

Both endpoints return a `suppression_list` array of entries; this class
exposes them as `list<SuppressionEntry>` for typed iteration:

```php
foreach ($client->suppressions->list()->suppressionList ?? [] as $entry) {
    echo $entry->recipient, ' (', $entry->source, ")\n";
}
```

Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
helpers.

### Public Properties

- `readonly ?array $suppressionList`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\UsageResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response returned by {@see \MailChannels\Resource\Usage::retrieve()}.

`$totalUsage` is the count of accepted send-requests for the current
billing period; `$periodStartDate` / `$periodEndDate` bracket the period
the count covers. All fields are nullable — missing fields stay null so
the SDK stays forward-compatible with future API additions.

Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
helpers.

### Public Properties

- `readonly ?string $periodEndDate`
- `readonly ?string $periodStartDate`
- `readonly ?int $totalUsage`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type. Missing fields are nulled out.


## `MailChannels\Response\WebhookBatch`

_final class_

One webhook batch record returned in {@see WebhookBatchListResponse::$webhookBatches}. Spec schema: `WebhookBatch`.

`$status` carries the response-form enum (`1xx_response` /
`2xx_response` / ... / `no_response`) — distinct from the query-form
filter used by {@see \MailChannels\Enum\WebhookBatchStatus} (which
lacks the `_response` suffix). Exposed as a plain string here to
mirror the wire value exactly.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?int $batchId`
- `readonly ?string $createdAt`
- `readonly ?string $customerHandle`
- `readonly ?MailChannels\Response\WebhookBatchDuration $duration`
- `readonly ?int $eventCount`
- `readonly ?string $status`
- `readonly ?int $statusCode`
- `readonly ?string $webhook`

### Constructor

```php
public function __construct(
    ?int $batchId = null,
    ?string $customerHandle = null,
    ?string $webhook = null,
    ?string $status = null,
    ?int $statusCode = null,
    ?int $eventCount = null,
    ?string $createdAt = null,
    ?MailChannels\Response\WebhookBatchDuration $duration = null
)
```

**Parameters:**
- `$batchId` (`?int`)
- `$customerHandle` (`?string`)
- `$webhook` (`?string`)
- `$status` (`?string`)
- `$statusCode` (`?int`)
- `$eventCount` (`?int`)
- `$createdAt` (`?string`)
- `$duration` (`?MailChannels\Response\WebhookBatchDuration`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\WebhookBatchDuration`

_final class_

Duration of a webhook batch attempt — how long the receiving endpoint took to respond. Spec schema: `WebhookBatchDuration`. `$unit` is always `"milliseconds"` today but is exposed as a string for forward- compat in case the API ever adds other units.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?string $unit`
- `readonly ?int $value`

### Constructor

```php
public function __construct(
    ?int $value = null,
    ?string $unit = null
)
```

**Parameters:**
- `$value` (`?int`)
- `$unit` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\WebhookBatchListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /webhook-batch`. Spec schema: `WebhookBatchResult`. Wraps a list of {@see WebhookBatch} entries under the `webhook_batches` JSON key.

### Public Properties

- `readonly ?array $webhookBatches`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\WebhookEndpointResponse`

_final class_

The HTTP response MailChannels received from a customer's webhook endpoint during a validation test. Spec schema: `WebhookResponse` (renamed in the SDK to avoid colliding with the SDK's own {@see \MailChannels\Response}).

A null status indicates no response was received (timeout, connection
failure, etc.).

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?string $body`
- `readonly ?int $status`

### Constructor

```php
public function __construct(
    ?int $status = null,
    ?string $body = null
)
```

**Parameters:**
- `$status` (`?int`)
- `$body` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\WebhookListResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /webhook`. The API returns a bare JSON array of {@see WebhookSubscription} entries (no envelope); the SDK's {@see \MailChannels\Transport} wraps bare arrays under the `data` key in the decoded body, and this class extracts the typed list from there.

```php
foreach ($client->webhooks->list()->webhooks ?? [] as $sub) {
    echo $sub->webhook, "\n";
}
```

### Public Properties

- `readonly ?array $webhooks`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\WebhookPublicKeyResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `GET /webhook/public-key`. Spec schema: `Key`.

`$id` is the key identifier referenced by the `keyid` parameter in
webhook signature headers; `$key` is the public key body (typically
base64-encoded Ed25519) used by
{@see \MailChannels\Webhook\Verifier} to verify inbound signatures.

### Public Properties

- `readonly ?string $id`
- `readonly ?string $key`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\WebhookResendResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `POST /webhook-batch/{batch_id}/resend`. Spec schema: `WebhookResendResponse`.

Similar in shape to {@see WebhookBatch} but flatter: duration is
inlined as `$durationInMs` (nullable int when no response was
received) instead of a nested object, and there's no top-level
`status` enum.

Note: the body field `status_code` (the HTTP status returned by the
customer's webhook endpoint during the resend) is exposed as
`$webhookStatusCode` rather than `$statusCode` to avoid shadowing
the inherited {@see Response::$statusCode} (which is MailChannels'
own HTTP response status for this SDK call).

### Public Properties

- `readonly ?int $batchId`
- `readonly ?string $createdAt`
- `readonly ?string $customerHandle`
- `readonly ?int $durationInMs`
- `readonly ?int $eventCount`
- `readonly ?string $webhook`
- `readonly ?int $webhookStatusCode`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Response\WebhookSubscription`

_final class_

One enrolled webhook subscription entry returned by `GET /webhook`. Spec schema: `Webhook` (renamed in the SDK to avoid confusion with {@see \MailChannels\Resource\Webhooks}).

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?string $webhook`

### Constructor

```php
public function __construct(
    ?string $webhook = null
)
```

**Parameters:**
- `$webhook` (`?string`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\WebhookValidationResult`

_final class_

One per-webhook entry in {@see WebhookValidationResultsResponse::$results}. Spec schema: `WebhookValidationResult`.

`$result` is `"passed"` when the endpoint returned a 2xx during the
validation test, `"failed"` otherwise. `$response` carries the actual
HTTP response MailChannels received.

Plain value object — not a `Response` subclass.

### Public Properties

- `readonly ?MailChannels\Response\WebhookEndpointResponse $response`
- `readonly ?string $result`
- `readonly ?string $webhook`

### Constructor

```php
public function __construct(
    ?string $webhook = null,
    ?string $result = null,
    ?MailChannels\Response\WebhookEndpointResponse $response = null
)
```

**Parameters:**
- `$webhook` (`?string`)
- `$result` (`?string`)
- `$response` (`?MailChannels\Response\WebhookEndpointResponse`)

### Methods

#### `fromArray()`

```php
public static function fromArray(
    array $data
): self
```

**Parameters:**
- `$data` (`array`)

**Returns:** `self`

**Throws:**
- ResponseValidationException when a present field has the wrong type.

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```

**Returns:** `array<string,` — mixed>


## `MailChannels\Response\WebhookValidationResultsResponse`

_final class_ — extends [`MailChannels\Response`](#mailchannelsresponse)

Typed response for `POST /webhook/validate`. Spec schema: `WebhookValidationResults`.

`$allPassed` is true when every enrolled webhook returned a 2xx HTTP
status during the validation test. `$results` carries the per-
webhook outcomes including the actual HTTP response MailChannels
received from each endpoint.

```php
$results = $client->webhooks->validate();
if ($results->allPassed !== true) {
    foreach ($results->results ?? [] as $r) {
        if ($r->result === 'failed') {
            echo $r->webhook, ' failed: HTTP ', $r->response?->status, "\n";
        }
    }
}
```

### Public Properties

- `readonly ?bool $allPassed`
- `readonly ?array $results`

### Constructor

```php
public function __construct(
    MailChannels\Response $response
)
```

**Parameters:**
- `$response` (`MailChannels\Response`)

**Throws:**
- ResponseValidationException when a present field has the wrong type.


## `MailChannels\Transport`

_final class_

Internal HTTP transport. Builds PSR-7 requests, sends them through a PSR-18 client, and decodes responses into MailChannels\Response — or raises a typed exception from the MailChannels\Exception namespace.

### Constructor

```php
public function __construct(
    MailChannels\Config $config
)
```

**Parameters:**
- `$config` (`MailChannels\Config`)

### Methods

#### `request()`

```php
public function request(
    string $method,
    string $path,
    ?array $jsonBody = null,
    ?array $query = null,
    array $extraHeaders = [],
    bool $requireApiKey = true
): MailChannels\Response
```

**Parameters:**
- `$method` (`string`)
- `$path` (`string`)
- `$jsonBody` (`?array`)
- `$query` (`?array`)
- `$extraHeaders` (`array`)
- `$requireApiKey` (`bool`)

**Returns:** `MailChannels\Response`


## `MailChannels\Version`

_final class_

### Constants

- `VERSION = '1.0.1'`

### Methods

#### `userAgent()`

```php
public static function userAgent(): string
```

**Returns:** `string`


## `MailChannels\Webhook\SignatureParameters`

_final class_

Parsed RFC 9421 Signature-Input header parameters.

### Public Properties

- `readonly ?string $algorithm`
- `readonly array $coveredComponents`
- `readonly ?int $created`
- `readonly ?string $keyId`
- `readonly string $raw`
- `readonly string $signatureName`

### Constructor

```php
public function __construct(
    string $signatureName,
    array $coveredComponents,
    ?int $created = null,
    ?string $algorithm = null,
    ?string $keyId = null,
    string $raw = ''
)
```

**Parameters:**
- `$signatureName` (`string`)
- `$coveredComponents` (`list<string>`)
- `$created` (`?int`)
- `$algorithm` (`?string`)
- `$keyId` (`?string`)
- `$raw` (`string`)


## `MailChannels\Webhook\Verifier`

_final class_

RFC 9421 helpers for MailChannels webhook payloads. The Verifier parses the Signature-Input header, verifies the Content-Digest, checks signature freshness, and reconstructs the canonical signature base that the signer computed.

Ed25519 signature verification itself is left to the caller's preferred
crypto library (`sodium_crypto_sign_verify_detached`, `openssl`, the
paragonie/sodium_compat polyfill, etc.) so this package does not pull in
a crypto dependency. See README for a worked example.

### Methods

#### `buildSignatureBase()`

```php
public static function buildSignatureBase(
    string $method,
    string $targetUri,
    array $headers,
    string $body,
    MailChannels\Webhook\SignatureParameters $parameters
): string
```

Reconstruct the canonical RFC 9421 signature base that the signer computed before producing the Signature header. Pass this string to your crypto library together with the raw signature bytes and the public key to verify authenticity:

$base = Verifier::buildSignatureBase('POST', $url, $headers, $body, $params);
  sodium_crypto_sign_verify_detached($sigBytes, $base, $publicKeyBytes);

Supported covered components:
  - Derived: @method, @target-uri, @authority, @scheme, @path,
    @query, @request-target
  - Any HTTP header by lowercase name (value trimmed)

**Parameters:**
- `$method` (`string`)
- `$targetUri` (`string`)
- `$headers` (`array`)
- `$body` (`string`)
- `$parameters` (`MailChannels\Webhook\SignatureParameters`)

**Returns:** `string`

#### `parseSignatureInput()`

```php
public static function parseSignatureInput(
    string $value
): MailChannels\Webhook\SignatureParameters
```

**Parameters:**
- `$value` (`string`)

**Returns:** `MailChannels\Webhook\SignatureParameters`

#### `signatureIsFresh()`

```php
public static function signatureIsFresh(
    MailChannels\Webhook\SignatureParameters $parameters,
    int $toleranceSeconds = 300,
    ?int $now = null
): bool
```

**Parameters:**
- `$parameters` (`MailChannels\Webhook\SignatureParameters`)
- `$toleranceSeconds` (`int`)
- `$now` (`?int`)

**Returns:** `bool`

#### `signatureKeyId()`

```php
public static function signatureKeyId(
    array $headers
): ?string
```

**Parameters:**
- `$headers` (`array`)

**Returns:** `?string`

#### `verifyContentDigest()`

```php
public static function verifyContentDigest(
    array $headers,
    string $body
): bool
```

**Parameters:**
- `$headers` (`array`)
- `$body` (`string`)

**Returns:** `bool`


