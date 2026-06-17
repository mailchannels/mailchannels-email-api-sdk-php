# Setting Up The MailChannels PHP Client

Before any call works the PHP SDK needs to know **which API key to send**,
and optionally where to send it and which HTTP transport to use. This
document is the one-stop reference for that setup.

## TL;DR

```php
use MailChannels\Client;

// Set MAILCHANNELS_API_KEY in the environment; the SDK reads it
// automatically and no PHP-side configuration is needed.
//   export MAILCHANNELS_API_KEY="YOUR-API-KEY"
$client = new Client();
$client->emails->queue([...]);
```

That covers single-account use. The rest of this document covers the
choices you have when the simple path doesn't fit: per-tenant clients,
custom PSR-18 transports, logging, and the (rare) base-URL override.

## API Keys

### Precedence Order

When the SDK needs an API key it walks this chain and uses the first
non-empty value it finds:

1. The `apiKey` argument passed to `new Client(apiKey: '...')`.
2. The `MAILCHANNELS_API_KEY` environment variable.

If nothing in the chain is set, the first API call throws
`ConfigurationException` with code `MissingApiKey`. The resolved key is
sent as the `X-Api-Key` header on every request.

### Where To Get Them

Parent-account keys are created in the MailChannels dashboard.
Sub-account keys are created programmatically from the parent account:

```php
$parent = new Client(apiKey: 'PARENT-ACCOUNT-API-KEY');
$key = $parent->subAccounts->apiKeys->create('tenanthandle');
// Full value returned ONCE — persist it to your secret store immediately.
```

See [sub-accounts](sub-accounts.md) for the full sub-account workflow.

### Multi-Tenant: One Client Per Credential

Never mix a parent key and a sub-account key on the same instance.
Give each tenant its own `Client`:

```php
$parent  = new Client(apiKey: 'PARENT-ACCOUNT-API-KEY');
$tenantA = new Client(apiKey: 'TENANT-A-API-KEY');
$tenantB = new Client(apiKey: 'TENANT-B-API-KEY');

$parent->subAccounts->list();
$tenantA->emails->queue([...]);
$tenantB->emails->queue([...]);
```

## Base URL (Rarely Needed)

The default base URL is `https://api.mailchannels.net/tx/v1`. Almost no
application should override it — only do so if MailChannels has given
you a specific alternate endpoint (sandbox or regional URL).

Override via constructor:

```php
$client = new Client(apiKey: '...', baseUrl: 'https://example.com/tx/v1');
```

Or via environment variable `MAILCHANNELS_API_URL`.

## Client Lifecycle

Create a single `Client` per credential per process and reuse it. The
underlying PSR-18 transport pools connections, and constructing a new
`Client` on every request wastes that pool.

```php
// In a DI container — create once, inject everywhere.
$client = new Client();
```

There is no explicit `close()` — the PSR-18 transport cleans up when the
object is garbage-collected or the process exits.

## Custom PSR-18 HTTP Transport

The SDK accepts any PSR-18 `ClientInterface`. Inject one when you need
outbound proxies, custom timeouts, retry middleware, mTLS, or a test fake.

PSR-18 has no portable API for timeouts or retries — configure those on
the underlying client:

### Guzzle

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use MailChannels\Client;

$factory = new HttpFactory();
$client  = new Client(
    httpClient:     new GuzzleClient(['timeout' => 10, 'http_errors' => false]),
    requestFactory: $factory,
    streamFactory:  $factory,
);
```

`http_errors => false` is important — the SDK inspects status codes and
raises its own typed exceptions; you don't want Guzzle to throw first.

### Symfony HTTP Client + Nyholm PSR-7

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use MailChannels\Client;

$psr17  = new Psr17Factory();
$client = new Client(
    httpClient:     new Psr18Client(HttpClient::create(['max_duration' => 10])),
    requestFactory: $psr17,
    streamFactory:  $psr17,
);
```

### Auto-Discovery Warning

When no `httpClient` is passed, the SDK uses
`php-http/discovery` to find an installed PSR-18 client. If **both**
Guzzle and Symfony HTTP Client are installed, discovery picks one
deterministically but logs a warning via the configured PSR-3 logger.
Pass `httpClient` explicitly to silence the warning and pin routing.

## PSR-3 Logging

Pass any `Psr\Log\LoggerInterface` to receive structured logs for
requests, resource-level actions, and API errors. The SDK never logs API
keys or request bodies.

```php
use Psr\Log\NullLogger;
use MailChannels\Client;

// Any PSR-3 LoggerInterface works — swap NullLogger for Monolog, your framework logger, etc.
$client = new Client(logger: new NullLogger());
```

## Using `Config` For DI Containers

Build a `Config` object separately when a DI container provides a
pre-configured singleton:

```php
use MailChannels\Config;
use MailChannels\Client;
use Psr\Log\LoggerInterface;

// $logger comes from your DI container or framework bootstrap
$config = new Config(
    apiKey:  getenv('MAILCHANNELS_API_KEY') ?: null,
    logger:  $logger,
);
$client = Client::fromConfig($config);
```

`Config::withApiKey(string $apiKey)` produces a copy with a different
key — useful for creating per-tenant clients from a shared base config:

```php
$tenantClient = Client::fromConfig($baseConfig->withApiKey($tenantKey));
```

## User-Agent Override

The SDK sends `mailchannels-php/{version}` as the `User-Agent` by default.
Override it when you embed the SDK in a platform and want MailChannels
support to see your platform identifier:

```php
$client = new Client(apiKey: '...', userAgent: 'MyPlatform/1.0 mailchannels-php');
```
