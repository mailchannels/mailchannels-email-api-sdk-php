# MailChannels PHP SDK

[![Build Status](https://img.shields.io/bitbucket/pipelines/mailchannels/mailchannels-email-api-sdk-php/main.svg)](https://bitbucket.org/mailchannels/mailchannels-email-api-sdk-php/pipelines)
[![Latest Version](https://img.shields.io/packagist/v/mailchannels/mailchannels-php.svg)](https://packagist.org/packages/mailchannels/mailchannels-php)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-max-brightgreen.svg)](https://phpstan.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

> Built and tested against Email API 1.3.0 (base path `/tx/v1`, spec at
> [docs.mailchannels.net/email-api.yaml](https://docs.mailchannels.net/email-api.yaml)).

`mailchannels/mailchannels-php` is the official PHP SDK for the
[MailChannels Email API](https://docs.mailchannels.net/email-api). It is a
hand-written client built around the
[PHP-FIG](https://www.php-fig.org/) standards: it depends only on PSR
interfaces and discovers concrete implementations at runtime, so it works with
**Guzzle**, **Symfony HTTP Client**, or any other PSR-18 client you already
have in your project.

The SDK covers the full Email API surface: transactional sending, server-side
queued sending (`/send-async`), MailChannels-hosted DKIM, sub-account
provisioning, suppressions, metrics, webhook enrollment, and RFC 9421
signature verification.

## Standards alignment

| PSR    | Where used                                                                    |
| ------ | ----------------------------------------------------------------------------- |
| PSR-1  | Basic coding standard.                                                        |
| PSR-3  | Pass any `Psr\Log\LoggerInterface` to the client for structured request logs. |
| PSR-7  | All wire-level requests and responses are PSR-7 messages.                     |
| PSR-17 | Request / stream factories injected via the client constructor.               |
| PSR-18 | The HTTP transport is a PSR-18 `ClientInterface`.                             |

## Install

```bash
composer require mailchannels/mailchannels-php
```

You also need a PSR-18 client and PSR-17 factories. **Install exactly one** of
the options below — installing both can confuse the auto-discovery
mechanism and lead to surprising routing of HTTP traffic (the SDK will log
a warning if both are present):

```bash
# Option A — Guzzle (most popular; PSR-18 client + PSR-17 factories in one package)
composer require guzzlehttp/guzzle

# Option B — Symfony HTTP Client + Nyholm PSR-7
composer require symfony/http-client nyholm/psr7
```

[`php-http/discovery`](https://packagist.org/packages/php-http/discovery) is bundled, so
once one implementation is installed the SDK finds it automatically without
extra wiring. If you genuinely need both libraries in the same project for
unrelated reasons, pass the PSR-18 client you want the SDK to use
explicitly — see [Custom HTTP client](#custom-http-client) below.

## Authentication

Pass the API key as a constructor argument or set the environment variable
`MAILCHANNELS_API_KEY`. The base URL defaults to
`https://api.mailchannels.net/tx/v1`; override with `MAILCHANNELS_API_URL` or
the `baseUrl` constructor argument.

```php
use MailChannels\Client;

$client = new Client(apiKey: '...');
```

For multi-tenant services, create one client per credential:

```php
$parent = new Client(apiKey: getenv('PARENT_API_KEY'));
$tenant = new Client(apiKey: getenv('TENANT_API_KEY'));
```

## Send your first email

```php
use MailChannels\Client;

$client = new Client(apiKey: 'mc-...');

$response = $client->emails->queue([
    'from'    => ['email' => 'sender@example.com', 'name' => 'Priya Patel'],
    'to'      => 'recipient@example.net',
    'subject' => 'Welcome to MailChannels',
    'text'    => 'Plain-text body.',
    'html'    => '<p>HTML body.</p>',
]);

echo "Queued at {$response->queuedAt}, request_id={$response->requestId()}\n";
```

`queue()` posts to `/send-async`, the recommended path for production
traffic. The API acknowledges with a queue receipt — a `request_id` and
`queued_at` timestamp — without waiting for SMTP handoff, so client
latency stays low. Per-message delivery status arrives later via
[webhooks](#verifying-inbound-webhooks); the `request_id` ties webhook
events back to this call.

`$response` is a `MailChannels\Response\QueuedSendResponse` — typed
properties layered on top of array access and the inherited HTTP
metadata:

```php
$response->queuedAt;       // typed ?string (ISO 8601)
$response->statusCode;     // 202
$response->requestId();    // body's request_id, falling back to X-Request-ID
$response->headers;        // all response headers
$response['queued_at'];    // array access still works
```

### Strongly-typed payloads

When you want explicit validation in your codebase, build an `EmailParams`
object. The constructors of `EmailParams` and `Personalization` take many
optional fields — **always use named arguments** rather than positional ones,
both for readability and so that future spec additions don't shift positions:

```php
use MailChannels\Message\{EmailAddress, EmailParams, Personalization, Content, Attachment};

$params = new EmailParams(
    from: new EmailAddress('sender@example.com', 'Priya Patel'),
    subject: 'Receipt #4218',
    personalizations: [
        new Personalization(to: ['recipient@example.net']),
    ],
    content: [Content::html('<p>Thanks for your order.</p>')],
    attachments: [Attachment::fromBytes(
        (string) file_get_contents('/tmp/receipt.pdf'),
        'receipt.pdf',
        'application/pdf',
    )],
    transactional: true,
);

$client->emails->queue($params);
```

`EmailAddress`, `Content`, `Personalization`, `Attachment`, and `EmailParams`
all validate at construction. Reserved headers (`From`, `To`, `Subject`,
`Reply-To`, etc.) raise `MailChannels\Exception\ValidationException` rather
than silently overriding the structured fields.

### Synchronous send (`/send`)

Use `send()` when you need per-personalization results in the HTTP
response (for example to log the message id for each recipient
immediately), or for dry-runs that return rendered RFC822 bodies:

```php
$response = $client->emails->send($params);

foreach ($response->results ?? [] as $result) {
    echo $result->messageId, ' (', $result->status, ")\n";
}

// Dry-run: returns rendered messages instead of sending.
$preview = $client->emails->send($params, dryRun: true);
foreach ($preview->dryRunData ?? [] as $rendered) {
    echo $rendered, "\n";
}
```

Both `queue()` and `send()` are **server-side** operations — the HTTP
call itself is still synchronous. For client-side parallelism, use
your PSR-18 client's pool features (e.g. Guzzle's `pool()`).

## Errors

Every API failure raises a typed exception in the
`MailChannels\Exception` namespace:

| HTTP status | Exception                                              |
| ----------- | ------------------------------------------------------ |
| 401         | `MailChannels\Exception\AuthenticationException`       |
| 403         | `MailChannels\Exception\ForbiddenException`            |
| 404         | `MailChannels\Exception\NotFoundException`             |
| 409         | `MailChannels\Exception\ConflictException`             |
| 413         | `MailChannels\Exception\PayloadTooLargeException`      |
| 429         | `MailChannels\Exception\RateLimitException`            |
| 4xx other   | `MailChannels\Exception\InvalidRequestException`       |
| 502         | `MailChannels\Exception\BadGatewayException`           |
| 5xx other   | `MailChannels\Exception\ServerException`               |
| Transport   | `MailChannels\Exception\TransportException`            |
| SDK config  | `MailChannels\Exception\ConfigurationException`        |
| Validation  | `MailChannels\Exception\ValidationException`           |

All extend `MailChannels\Exception\MailChannelsException` (which extends
`RuntimeException`). On `RateLimitException`, `$e->getRetryAfter()` carries
the `Retry-After` header verbatim. `$e->getRequestId()` is set automatically
from any of the documented request-id headers.

The SDK does **not** retry by default. If you need retries, wrap the call —
PSR-18 clients like Guzzle let you compose retry middleware externally.

## Other resources

```php
// Sub-accounts (multi-tenant sending). `companyName` is required.
// Handles are optional — if omitted, MailChannels assigns one. When
// provided, handles must contain only lowercase letters and digits.
$client->subAccounts->create(companyName: 'ACME', handle: 'tenant42');
$client->subAccounts->limits->set('tenant42', sends: 50000);
$client->subAccounts->apiKeys->create('tenant42');
$client->subAccounts->retrieveUsage('tenant42');

// MailChannels-hosted DKIM
use MailChannels\Enum\DkimAlgorithm;
$client->dkim->create('example.com', selector: 'mc1', algorithm: DkimAlgorithm::Rsa);
$client->dkim->rotate('example.com', 'mc1', 'mc2');

// Domain checks (DKIM/SPF/Lockdown)
$client->checkDomain->check('example.com');

// Metrics
use MailChannels\Enum\MetricsInterval;
$client->metrics->engagement(
    startTime: '2026-05-01T00:00:00Z',
    endTime: '2026-05-08T00:00:00Z',
    interval: MetricsInterval::Day,
);

// Suppressions
$client->suppressions->list();
$client->suppressions->create([
    [
        'recipient' => 'bouncer@example.com',
        'suppression_types' => ['transactional', 'non-transactional'],
        'notes' => 'Hard-bounced 2026-05-14',
    ],
]);

// Account usage
$client->usage->retrieve();

// Webhooks
$client->webhooks->create('https://your-app.example.com/hooks/mailchannels');
$client->webhooks->validate();
```

## Verifying inbound webhooks

MailChannels signs webhook deliveries with Ed25519 per RFC 9421. The SDK
includes parser helpers; Ed25519 verification itself is delegated to your
preferred crypto library so we don't pull in another dependency:

```php
use MailChannels\Webhook\Verifier;

$bodyBytes = file_get_contents('php://input');
$headers   = getallheaders();

// 1. Check the SHA-256 digest matches the raw body.
if (!Verifier::verifyContentDigest($headers, $bodyBytes)) {
    http_response_code(400);
    return;
}

// 2. Parse the Signature-Input header.
$params = Verifier::parseSignatureInput($headers['Signature-Input']);

// 3. Check the signature is fresh.
if (!Verifier::signatureIsFresh($params)) {
    http_response_code(400);
    return;
}

// 4. Verify the signature itself (left to your crypto library, e.g. libsodium).
$publicKey = $client->webhooks->publicKey($params->keyId)->get('key');
$signature = base64_decode(/* extract from Signature header */);
$signedBase = /* RFC 9421 signature base from $params->coveredComponents */;

if (!sodium_crypto_sign_verify_detached($signature, $signedBase, base64_decode($publicKey))) {
    http_response_code(400);
    return;
}
```

See [`examples/webhook-verify.php`](examples/webhook-verify.php) for a
runnable starting point.

## Custom HTTP client

If you want to inject a specific PSR-18 client (for routing through a proxy,
adding middleware, etc.), pass it explicitly. The SDK does **not** apply its
own timeouts or retries — PSR-18 has no portable API for either, so they must
be configured on the underlying client (Guzzle's `timeout`, Symfony's
`max_duration`, etc.):

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use MailChannels\Client;

$factory = new HttpFactory();
$client = new Client(
    apiKey:         'mc-...',
    httpClient:     new GuzzleClient(['timeout' => 10, 'http_errors' => false]),
    requestFactory: $factory,
    streamFactory:  $factory,
);
```

## Logging

Pass any `Psr\Log\LoggerInterface` to receive structured logs for requests,
resource actions, and errors:

```php
use Monolog\Logger;

$logger = new Logger('mailchannels');
$client = new Client(apiKey: 'mc-...', logger: $logger);
```

The SDK never logs API keys or request bodies.

## Further reading

- [`docs/API_COVERAGE.md`](docs/API_COVERAGE.md) — full route map.
- [`docs/ADVANCED.md`](docs/ADVANCED.md) — version/user-agent, client
  reuse, multi-tenancy, custom HTTP transports, PSR-3 logging, the
  PSR-18 discovery warning, error-handling reference, and low-level
  webhook helpers.
- [`docs/DKIM.md`](docs/DKIM.md) — hosted vs customer-managed DKIM keys,
  key lifecycle, and a Cloudflare DNS publication recipe.
- [`examples/`](examples/) — runnable scripts for each endpoint group.

## Using with an AI agent

This repository ships a complete agent skill at
[`.agents/skills/mailchannels-php/`](.agents/skills/mailchannels-php/).
It teaches an AI coding agent how to use the `mailchannels/mailchannels-php`
package correctly by breaking the documentation down into focused
per-topic files with a decision tree so the agent loads only the
parts the task actually needs.

Layout:

```
.agents/skills/mailchannels-php/
├── SKILL.md              # entry point: scope, decision tree, conventions
└── resources/            # focused recipes (sending, attachments, webhooks, …)
```

Every file is plain Markdown. The skill works with any agent that can be
pointed at a directory of context files — Claude Code, Cursor, Codex CLI,
Aider, Continue, and similar tools all consume it without modification.

### Install

The skill ships inside the `mailchannels/mailchannels-php` Composer package.
Once the SDK is installed in your project, copy the skill directory wherever
your agent looks for skills, rules, or context files:

```bash
composer require mailchannels/mailchannels-php

# Replace the destination with your agent's path:
mkdir -p <your-agent-skills-dir>
cp -r vendor/mailchannels/mailchannels-php/.agents/skills/mailchannels-php <your-agent-skills-dir>/
```

Re-run the copy after upgrading the SDK so the skill stays in step with the
installed version:

```bash
composer update mailchannels/mailchannels-php
cp -r vendor/mailchannels/mailchannels-php/.agents/skills/mailchannels-php <your-agent-skills-dir>/
```

Common destinations:

| Agent | Where to put it |
| --- | --- |
| Claude Code | `.claude/skills/` (project) or `~/.claude/skills/` (user) |
| Cursor | `.cursor/rules/` (or attach files inline with `@`) |
| Codex CLI | referenced from the project's `AGENTS.md` |
| Aider | referenced from the conventions file in `.aider.conf.yml` |
| Continue | registered as a custom context provider |

If your tool isn't listed, look for the equivalent of "skill", "rule",
"context bundle", or "conventions file" — any mechanism that lets the agent
read a directory of Markdown will work.

## Development

```bash
composer install
composer test          # PHPUnit (unit + integration)
composer phpstan       # static analysis
```

## License

MIT. See [`LICENSE`](LICENSE).
