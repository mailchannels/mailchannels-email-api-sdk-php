# MailChannels PHP SDK API Reference

This reference is generated from public class signatures and PHPDoc blocks in `src/` by `scripts/generate-api-reference.php`.

For task-oriented examples, start with the [README](../README.md). This file is the formal public surface reference.

- SDK version: `0.1.0`
- Top-level exports: `49`
- Documented public methods: `134`

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
| `MailChannels\Response` | final class | Response object returned by SDK calls. Behaves as the decoded JSON body (array-accessible and iterable) while also exposing HTTP metadata. |
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

#### `fromFile()`

```php
public static function fromFile(
    string $path,
    ?string $filename = null,
    ?string $contentType = null,
    string $disposition = 'attachment',
    ?string $contentId = null
): self
```

**Parameters:**
- `$path` (`string`)
- `$filename` (`?string`)
- `$contentType` (`?string`)
- `$disposition` (`string`)
- `$contentId` (`?string`)

**Returns:** `self`

#### `inlineFile()`

```php
public static function inlineFile(
    string $path,
    string $contentId,
    ?string $filename = null,
    ?string $contentType = null
): self
```

**Parameters:**
- `$path` (`string`)
- `$contentId` (`string`)
- `$filename` (`?string`)
- `$contentType` (`?string`)

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
): MailChannels\Response
```

**Parameters:**
- `$domain` (`string`)
- `$senderId` (`?string`)
- `$dkimSettings` (`?array`)

**Returns:** `MailChannels\Response`


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
): MailChannels\Response
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`string`)
- `$algorithm` (`MailChannels\Enum\DkimAlgorithm|string|null`)
- `$keyLength` (`?int`)

**Returns:** `MailChannels\Response`

#### `list()`

```php
public function list(
    string $domain,
    ?string $selector = null,
    MailChannels\Enum\DkimKeyStatus|string|null $status = null,
    ?int $offset = null,
    ?int $limit = null,
    ?bool $includeDnsRecord = null
): MailChannels\Response
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`?string`)
- `$status` (`MailChannels\Enum\DkimKeyStatus|string|null`)
- `$offset` (`?int`)
- `$limit` (`?int`)
- `$includeDnsRecord` (`?bool`)

**Returns:** `MailChannels\Response`

#### `rotate()`

```php
public function rotate(
    string $domain,
    string $selector,
    string $newSelector
): MailChannels\Response
```

**Parameters:**
- `$domain` (`string`)
- `$selector` (`string`)
- `$newSelector` (`string`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

Queue an email through `/send-async`. The server queues the message and returns a message ID immediately; delivery happens later. This is server-side async — it does NOT make the HTTP call itself async.

**Parameters:**
- `$params` (`MailChannels\Message\EmailParams|array`)

**Returns:** `MailChannels\Response`

#### `send()`

```php
public function send(
    MailChannels\Message\EmailParams|array $params,
    bool $dryRun = false
): MailChannels\Response
```

**Parameters:**
- `$params` (`MailChannels\Message\EmailParams|array`)
- `$dryRun` (`bool`)

**Returns:** `MailChannels\Response`


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
): MailChannels\Response
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response`

#### `performance()`

```php
public function performance(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response`

#### `recipientBehavior()`

```php
public function recipientBehavior(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response`

#### `recipientBehaviour()`

```php
public function recipientBehaviour(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response`

#### `senders()`

```php
public function senders(
    MailChannels\Enum\MetricsSenderType|string $senderType,
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?int $limit = null,
    ?int $offset = null,
    MailChannels\Enum\MetricsSortOrder|string|null $sortOrder = null
): MailChannels\Response
```

**Parameters:**
- `$senderType` (`MailChannels\Enum\MetricsSenderType|string`)
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$limit` (`?int`)
- `$offset` (`?int`)
- `$sortOrder` (`MailChannels\Enum\MetricsSortOrder|string|null`)

**Returns:** `MailChannels\Response`

#### `volume()`

```php
public function volume(
    DateTimeInterface|string|null $startTime = null,
    DateTimeInterface|string|null $endTime = null,
    ?string $campaignId = null,
    MailChannels\Enum\MetricsInterval|string|null $interval = null
): MailChannels\Response
```

**Parameters:**
- `$startTime` (`DateTimeInterface|string|null`)
- `$endTime` (`DateTimeInterface|string|null`)
- `$campaignId` (`?string`)
- `$interval` (`MailChannels\Enum\MetricsInterval|string|null`)

**Returns:** `MailChannels\Response`


## `MailChannels\Resource\SubAccountApiKeys`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`


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
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

#### `set()`

```php
public function set(
    string $handle,
    ?int $sends = null,
    ?int $monthlyLimit = null
): MailChannels\Response
```

Set the monthly sending limit for a sub-account. Either `sends` or its documented alias `monthlyLimit` must be provided; the request payload uses `sends`.

**Parameters:**
- `$handle` (`string`)
- `$sends` (`?int`)
- `$monthlyLimit` (`?int`)

**Returns:** `MailChannels\Response`


## `MailChannels\Resource\SubAccountSmtpPasswords`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `create()`

```php
public function create(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`


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
): MailChannels\Response
```

Create a sub-account. `companyName` is required by the API. `handle` is optional — if omitted, MailChannels assigns one. When provided, it must match {@see self::HANDLE_PATTERN}.

**Parameters:**
- `$companyName` (`string`)
- `$handle` (`?string`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

**Parameters:**
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response`

#### `retrieveUsage()`

```php
public function retrieveUsage(
    string $handle
): MailChannels\Response
```

**Parameters:**
- `$handle` (`string`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

**Parameters:**
- `$entries` (`array`)
- `$addToSubAccounts` (`?bool`)

**Returns:** `MailChannels\Response`

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
): MailChannels\Response
```

**Parameters:**
- `$recipient` (`?string`)
- `$source` (`MailChannels\Enum\SuppressionSource|string|null`)
- `$createdBefore` (`?string`)
- `$createdAfter` (`?string`)
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response`


## `MailChannels\Resource\Usage`

_final class_ — extends [`MailChannels\Resource\AbstractResource`](#mailchannelsresourceabstractresource)

### Methods

#### `retrieve()`

```php
public function retrieve(): MailChannels\Response
```

**Returns:** `MailChannels\Response`


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
): MailChannels\Response
```

**Parameters:**
- `$createdAfter` (`?string`)
- `$createdBefore` (`?string`)
- `$statuses` (`list<WebhookBatchStatus|string>|null`)
- `$webhook` (`?string`)
- `$limit` (`?int`)
- `$offset` (`?int`)

**Returns:** `MailChannels\Response`

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
public function list(): MailChannels\Response
```

**Returns:** `MailChannels\Response`

#### `publicKey()`

```php
public function publicKey(
    string $keyId
): MailChannels\Response
```

`GET /webhook/public-key?id=...` — does not require an API key.

**Parameters:**
- `$keyId` (`string`)

**Returns:** `MailChannels\Response`

#### `resendBatch()`

```php
public function resendBatch(
    int $batchId,
    string $customerHandle
): MailChannels\Response
```

`POST /webhook-batch/{batch_id}/resend`. Uses `X-Customer-Handle` header for authentication instead of an API key.

**Parameters:**
- `$batchId` (`int`)
- `$customerHandle` (`string`)

**Returns:** `MailChannels\Response`

#### `validate()`

```php
public function validate(
    ?string $requestId = null
): MailChannels\Response
```

**Parameters:**
- `$requestId` (`?string`)

**Returns:** `MailChannels\Response`

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

_final class_

Response object returned by SDK calls. Behaves as the decoded JSON body (array-accessible and iterable) while also exposing HTTP metadata.

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

- `VERSION = '0.1.0'`

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


